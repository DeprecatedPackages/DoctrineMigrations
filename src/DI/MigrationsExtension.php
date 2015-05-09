<?php

/**
 * This file is part of Zenify
 * Copyright (c) 2014 Tomas Votruba (http://tomasvotruba.cz)
 */

namespace Zenify\DoctrineMigrations\DI;

use Kdyby\Console\DI\ConsoleExtension;
use Nette\DI\CompilerExtension;
use Nette\Utils\AssertionException;
use Nette\Utils\Validators;


class MigrationsExtension extends CompilerExtension
{

	const CS_TABS = 'tabs';

	/**
	 * @var array
	 */
	private $defaults = array(
		'table' => 'doctrine_migrations',
		'dirs' => array(),
		'namespace' => 'Migrations',
		'enabled' => FALSE,
		'codingStandard' => self::CS_TABS # or "spaces"
	);


	public function __construct()
	{
		$this->defaults['enabled'] = PHP_SAPI === 'cli';
	}


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		if ($config['enabled'] === FALSE) {
			return;
		}
		$this->validateConfigTypes($config);

		$builder = $this->getContainerBuilder();

		if (count($config['dirs']) === 0) {
			$config['dirs'] = array($builder->expand('%appDir%/../migrations'));
		}

		$builder->addDefinition($this->prefix('consoleOutput'))
			->setClass('Zenify\DoctrineMigrations\OutputWriter');

		$configuration = $builder->addDefinition($this->prefix('configuration'))
			->setClass('Zenify\DoctrineMigrations\Configuration\Configuration')
			->addSetup('setMigrationsTableName', array($config['table']))
			->addSetup('setMigrationsDirectory', array(reset($config['dirs'])))
			->addSetup('setMigrationsNamespace', array($config['namespace']))
			->addSetup('setCs', array($config['codingStandard']));

		$dirs = array_unique($config['dirs']);
		foreach ($dirs as $dir) {
			$configuration->addSetup('registerMigrationsFromDirectory', array($dir));
		}

		foreach ($this->loadFromFile(__DIR__ . '/commands.neon') as $i => $class) {
			$builder->addDefinition($this->prefix('command.' . $i))
				->setClass($class)
				->addTag(ConsoleExtension::COMMAND_TAG)
				->addSetup('setMigrationConfiguration', array($configuration));
		}
	}


	/**
	 * @throws AssertionException
	 */
	private function validateConfigTypes(array $config)
	{
		Validators::assertField($config, 'table', 'string');
		Validators::assertField($config, 'dirs', 'list');
		Validators::assertField($config, 'namespace', 'string');
		Validators::assertField($config, 'codingStandard', 'string');
	}

}
