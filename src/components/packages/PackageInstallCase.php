<?php
namespace extas\components\packages;

use extas\components\options\CommandOptionRepository;
use extas\interfaces\stages\IStageInitialize;
use extas\interfaces\stages\IStageInitializeItem;
use extas\interfaces\stages\IStageInstall;
use extas\interfaces\stages\IStageInstallItem;
use extas\interfaces\stages\IStageInstallPackage;
use extas\interfaces\stages\IStageUninstall;
use extas\interfaces\stages\IStageUninstallItem;
use extas\interfaces\stages\IStageUninstallPackage;

use extas\commands\InitCommand;
use extas\commands\InstallCommand;
use extas\commands\UninstallCommand;
use extas\components\console\TSnuffConsole;
use extas\components\crawlers\Crawler;
use extas\components\crawlers\CrawlerRepository;
use extas\components\extensions\ExtensionRepository;
use extas\components\packages\entities\EntityRepository;
use extas\components\plugins\init\Init;
use extas\components\plugins\init\InitItem;
use extas\components\plugins\install\InstallApplication;
use extas\components\plugins\install\InstallItem;
use extas\components\plugins\install\InstallPackage;
use extas\components\plugins\PluginRepository;
use extas\components\plugins\TSnuffPlugins;
use extas\components\plugins\uninstall\UninstallApplication;
use extas\components\plugins\uninstall\UninstallItem;
use extas\components\plugins\uninstall\UninstallPackage;
use extas\components\repositories\TSnuffRepository;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class PackageInstallCase
 *
 * @package extas\components\packages
 * @author jeyroik <jeyroik@gmail.com>
 */
class PackageInstallCase extends TestCase
{
    use TSnuffConsole;
    use TSnuffRepository;
    use TSnuffPlugins;

    protected function setUp(): void
    {
        parent::setUp();
        $env = Dotenv::create(getcwd() . '/tests/');
        $env->load();
        $this->registerSnuffRepos([
            'pluginRepository' => PluginRepository::class,
            'entityRepository' => EntityRepository::class,
            'extensionRepository' => ExtensionRepository::class,
            'crawlerRepository' => CrawlerRepository::class,
            'commandOptionRepository' => CommandOptionRepository::class
        ]);
    }

    protected function tearDown(): void
    {
        $this->unregisterSnuffRepos();
    }

    public function testInitPackage()
    {
        /**
         * @var BufferedOutput $output
         */
        $output = $this->getOutput(true);
        $command = $this->getInitCommand();
        $command->run($this->getInput(), $output);

        $outputText = $output->fetch();

        $this->assertStringContainsString('Installing extensions...', $outputText);
        $this->assertStringContainsString('Installing plugins...', $outputText);
    }

    public function testInstallPackage()
    {
        /**
         * @var BufferedOutput $output
         */
        $output = $this->getOutput(true);
        $command = $this->getInstallCommand();
        $command->run(
            $this->getInput([
                'application' => 'package_test',
                'package_filename' => 'extas.json'
            ]),
            $output
        );
        $outputText = $output->fetch();
        $this->assertStringContainsString(
            'Installing application package_test with found packages...',
            $outputText,
            'Missed application name in the current output: ' . $outputText
        );
    }

    public function testUninstallPackage()
    {
        /**
         * @var BufferedOutput $output
         */
        $output = $this->getOutput(true);
        $command = $this->getUninstallCommand();
        $command->run(
            $this->getInput([
                'application' => 'package_test',
                'package_filename' => 'extas.json'
            ]),
            $output
        );
        $outputText = $output->fetch();
        $this->assertStringContainsString(
            'Uninstalling application package_test with found packages...',
            $outputText,
            'Missed application name in the current output: ' . $outputText
        );
    }

    /**
     * @return InitCommand
     * @throws \Exception
     */
    protected function getInitCommand(): InitCommand
    {
        $this->createWithSnuffRepo('crawlerRepository', new Crawler([
            Crawler::FIELD__CLASS => CrawlerExtas::class,
            Crawler::FIELD__TAGS => ['extas-package']
        ]));
        $this->createSnuffPlugin(Init::class, [IStageInitialize::NAME]);
        $this->createSnuffPlugin(InitItem::class, [IStageInitializeItem::NAME]);

        return new InitCommand();
    }

    /**
     * @return InstallCommand
     * @throws \Exception
     */
    protected function getInstallCommand(): InstallCommand
    {
        $this->createWithSnuffRepo('crawlerRepository', new Crawler([
            Crawler::FIELD__CLASS => CrawlerExtas::class,
            Crawler::FIELD__TAGS => ['extas-package']
        ]));
        $this->createSnuffPlugin(InstallApplication::class, [IStageInstall::NAME]);
        $this->createSnuffPlugin(InstallPackage::class, [IStageInstallPackage::NAME]);
        $this->createSnuffPlugin(InstallItem::class, [IStageInstallItem::NAME]);

        return new InstallCommand();
    }

    /**
     * @return UninstallCommand
     * @throws \Exception
     */
    protected function getUninstallCommand(): UninstallCommand
    {
        $this->createWithSnuffRepo('crawlerRepository', new Crawler([
            Crawler::FIELD__CLASS => CrawlerExtas::class,
            Crawler::FIELD__TAGS => ['extas-package']
        ]));
        $this->createSnuffPlugin(UninstallApplication::class, [IStageUninstall::NAME]);
        $this->createSnuffPlugin(UninstallPackage::class, [IStageUninstallPackage::NAME]);
        $this->createSnuffPlugin(UninstallItem::class, [IStageUninstallItem::NAME]);

        return new UninstallCommand();
    }
}
