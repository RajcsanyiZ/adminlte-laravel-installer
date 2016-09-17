<?php

namespace Acacha\AdminLTETemplateLaravel\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class InstallCommand.
 */
class InstallCommand extends Command
{
    /**
     * Avoids using llum to install package.
     *
     * @var bool
     */
    protected $noLlum = false;

    /**
     * Install development version.
     *
     * @var bool
     */
    protected $installDev = false;

    /**
     * Install using php artisan vendor:publish.
     *
     * @var bool
     */
    protected $useVendorPublish = false;

    /**
     * Install asking before overwrite files.
     *
     * @var bool
     */
    protected $askBeforeOverwrite = false;

    /**
     * Initialize command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        if ($input->hasOption('no-llum')) {
            $this->noLlum = $input->getOption('no-llum');
        }
        if ($input->hasOption('dev')) {
            $this->installDev = $input->getOption('dev');
        }
        if ($input->hasOption('use-vendor-publish')) {
            $this->useVendorPublish = $input->getOption('use-vendor-publish');
        }
        if ($input->hasOption('dontforce')) {
            $this->askBeforeOverwrite = $input->getOption('dontforce');
        }
    }

    /**
     * Check is --no-llum option is active.
     *
     * @return bool
     */
    private function isNoLlumActive()
    {
        return $this->noLlum;
    }

    /**
     * Configure the command options.
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this->setName('install')
            ->setDescription('Install Acacha AdminLTE package into the current project.');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->isNoLlumActive()) {
            $this->executeWithoutLlum($output);
        } else {
            $llum = $this->findLlum();
            $package = $this->getPackageName();
            $output->writeln('<info>'.$llum.' package '.$this->getDevOption()." $package".'</info>');
            passthru($llum.' package '.$this->getDevOption(). ' ' . $package);
        }
    }

    /**
     * Get llum package name
     */
    private function getPackageName() {
        if (! $this->askBeforeOverwrite) {
            return $this->useVendorPublish ? $package = 'AdminLTEVendorPublish' : $package = 'AdminLTE';
        }
        return $this->useVendorPublish ? $package = 'AdminLTEVendorPublishDontForce' : $package = 'AdminLTEDontForce';
    }

    /**
     * Execute command wiht option --no-llum.
     *
     * @param OutputInterface $output
     */
    protected function executeWithoutLlum(OutputInterface $output)
    {
        $composer = $this->findComposer();

        $process = new Process($composer.' require acacha/admin-lte-template-laravel'.$this->getDevSuffix(),
            null, null, null, null);

        $output->writeln(
            '<info>Running composer require acacha/admin-lte-template-laravel'.$this->getDevSuffix().'</info>');
        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<info>Copying file '.__DIR__.'/stubs/app.php'.' into '.getcwd().'/config/app.php</info>');
        copy(__DIR__.'/stubs/app.php', getcwd().'/config/app.php');

        $this->useVendorPublish ? $this->publishWithVendor($output) : $this->publish($output);
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    private function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar"';
        }

        return 'composer';
    }

    /**
     * Get the llum command for the environment.
     *
     * @return string
     */
    private function findLlum()
    {
        $HOME = $this->getUserHomePath();
        if (is_executable($this->getRealPath("$HOME/.composer/vendor/bin/llum"))) {
            return "$HOME/.composer/vendor/bin/llum";
        }
        if (is_executable($this->getRealPath("$HOME/.config/composer/vendor/bin/llum"))) {
            return "$HOME/.config/composer/vendor/bin/llum";
        }

        return 'llum';
    }

    /**
     * Get the real path of a link or regular path if file is not a link.
     *
     * @param $file
     *
     * @return string
     */
    private function getRealPath($file)
    {
        if (is_link($file)) {
            return realpath($file);
        }

        return $file;
    }

    /**
     * @return string
     */
    public function getUserHomePath()
    {
        if (isset($_SERVER['HOME'])) {
            return $_SERVER['HOME'];
        }

        if (PHP_OS == 'WINNT') {
            return getenv('USERPROFILE');
        } else {
            return getenv('HOME');
        }
    }

    /*
     * Gets dev option
     *
     * @return string
     */
    /**
     * @return string
     */
    private function getDevOption()
    {
        return $this->installDev ? '--dev' : '';
    }

    /*
     * Gets dev suffix
     *
     * @return string
     */
    /**
     * @return string
     */
    private function getDevSuffix()
    {
        return $this->installDev ? ':dev-master' : '';
    }

    /**
     * Manually publishes files to project.
     *
     * @param OutputInterface $output
     */
    protected function publish(OutputInterface $output)
    {
        $output->writeln('<info>php artisan adminlte:publish</info>');
        passthru('php artisan adminlte-laravel:publish');
    }

    /**
     * Publishes files with artisan publish command.
     *
     * @param OutputInterface $output
     */
    protected function publishWithVendor(OutputInterface $output)
    {
        $output->writeln('<info>php artisan vendor:publish --tag=adminlte --force</info>');
        passthru('php artisan vendor:publish --tag=adminlte --force');
    }
}
