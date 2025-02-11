<?php

namespace Castor\Import\Remote;

use Castor\Helper\PathHelper;
use Castor\Import\Exception\ImportError;
use Castor\Import\Exception\InvalidImportFormat;
use Castor\Import\Exception\RemoteNotAllowed;
use Castor\Import\Importer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** @internal */
class PackageImporter
{
    public function __construct(
        #[Autowire(lazy: true)]
        private readonly Importer $importer,
        private readonly InputInterface $input,
        private readonly LoggerInterface $logger,
        private readonly Composer $composer,
        /** @var array<string, Import> */
        private array $imports = [],
    ) {
    }

    /** @phpstan-param ImportSource $source */
    public function importPackage(string $scheme, string $package, ?string $file = null, ?string $version = null, ?string $vcs = null, ?array $source = null): void
    {
        if (!$this->allowsRemote()) {
            throw new RemoteNotAllowed('Remote imports are disabled.');
        }

        $requiredVersion = $version ?? '*';

        if (isset($this->imports[$package]) && $this->imports[$package]->version !== $requiredVersion) {
            throw new ImportError(sprintf('The package "%s" is already required in version "%s", could not require it in version "%s"', $package, $this->imports[$package]->version, $requiredVersion));
        }

        if (!preg_match('#^(?<organization>[^/]+)/(?<repository>[^/]+)$#', $package)) {
            throw new InvalidImportFormat(sprintf('The import path must be formatted like this: "%s://<organization>/<repository>".', $scheme));
        }

        if ('composer' === $scheme) {
            if (null !== $source) {
                throw new InvalidImportFormat('The "source" argument is not supported for Composer/Packagist packages.');
            }

            $this->importPackageWithComposer($package, version: $requiredVersion, repositoryUrl: $vcs, file: $file);

            return;
        }

        if ('package' === $scheme) {
            if (null !== $version || null !== $vcs) {
                throw new InvalidImportFormat('The "source" and "vcs" arguments are not supported for non-Composer packages.');
            }
            if (null === $source) {
                throw new InvalidImportFormat('The "source" argument is required for non-Composer packages.');
            }

            $this->importPackageWithComposer($package, version: 'v1', source: $source, file: $file);

            return;
        }

        throw new InvalidImportFormat(sprintf('The import scheme "%s" is not supported.', $scheme));
    }

    public function fetchPackages(): void
    {
        if (!$this->imports) {
            $this->composer->remove();

            return;
        }

        // Need to look for the raw options as the input is not yet parsed
        $forceUpdate = true !== $this->input->getParameterOption('--update-remotes', true);
        $displayProgress = 'list' !== $this->input->getFirstArgument();

        $autoloadPath = PathHelper::getRoot() . Composer::VENDOR_DIR . 'autoload.php';

        if (!file_exists($autoloadPath)) {
            $forceUpdate = true;
        }

        $this->composer->update($forceUpdate, $displayProgress);

        require_once $autoloadPath;

        foreach ($this->imports as $package => $import) {
            foreach ($import->getFiles() as $file) {
                $this->importer->import(PathHelper::getRoot() . Composer::VENDOR_DIR . $package . '/' . ($file ?? ''));
            }
        }
    }

    /**
     * @param ?array{
     *     url?: string,
     *     type?: "git" | "svn",
     *     reference?: string,
     * } $source
     */
    private function importPackageWithComposer(string $package, string $version, ?string $repositoryUrl = null, ?array $source = null, ?string $file = null): void
    {
        $this->logger->info('Importing remote package with Composer.', [
            'package' => $package,
            'version' => $version,
        ]);

        $json = $this->composer->getConfiguration();

        $json['require'][$package] = $version;

        if ($repositoryUrl) {
            $json['repositories'][] = [
                'type' => 'vcs',
                'url' => $repositoryUrl,
            ];
        }

        if ($source) {
            if (!isset($source['url'], $source['type'], $source['reference'])) {
                throw new ImportError('The "source" argument must contain "url", "type" and "reference" keys.');
            }

            $json['repositories'][] = [
                'type' => 'package',
                'package' => [
                    'name' => $package,
                    'version' => $version,
                    'source' => $source,
                ],
            ];
        }

        $this->composer->setConfiguration($json);

        $this->imports[$package] ??= new Import($version);
        $this->imports[$package]->addFile($file);
    }

    private function allowsRemote(): bool
    {
        if ($_SERVER['CASTOR_NO_REMOTE'] ?? false) {
            return false;
        }

        // Need to look for the raw options as the input is not yet parsed
        return true === $this->input->getParameterOption('--no-remote', true);
    }
}

class Import
{
    /** @var array<string|null> */
    private array $files;

    public function __construct(
        public readonly string $version,
    ) {
    }

    public function addFile(?string $file = null): void
    {
        $this->files[] = $file;
    }

    /** @return array<string|null> */
    public function getFiles(): array
    {
        return array_unique($this->files);
    }
}
