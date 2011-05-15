<?php

/**
 * Packages loading
 * @package redaxo5
 * @version svn:$Id$
 */

if(rex_core::isSetup())
{
  rex_addon::initialize(false);
  $packageOrder = rex_core::getProperty('setup_packages');
}
else
{
  rex_addon::initialize();
  $packageOrder = rex_core::getConfig('package-order', array());
}

// in the first run, we register all folders for class- and fragment-loading,
// so it is transparent in which order the addons are included afterwards.
foreach($packageOrder as $packageId)
{
  $package = rex_package::get($packageId);
  $folder = $package->getBasePath();

  // add package path for fragment loading
  if(is_readable($folder .'fragments'))
  {
    rex_fragment::addDirectory($folder .'fragments'.DIRECTORY_SEPARATOR);
  }
  // add addon path for class-loading
  if(is_readable($folder .'lib'))
  {
    rex_autoload::addDirectory($folder .'lib'.DIRECTORY_SEPARATOR);
  }
  // add addon path for i18n
  if(is_readable($folder .'lang'))
  {
    rex_i18n::addDirectory($folder .'lang');
  }
  // load package infos
  rex_packageManager::loadPackageInfos($package);
}

// now we actually include the addons logic
foreach($packageOrder as $packageId)
{
  $package = rex_package::get($packageId);
  $folder = $package->getBasePath();

  // include the addon itself
  if(is_readable($folder .'config.inc.php'))
  {
    //$manager = rex_packageManager::factory($package);
    rex_packageManager::includeFile($package, rex_packageManager::CONFIG_FILE);
  }
}

// ----- all addons configs included
rex_extension::registerPoint('ADDONS_INCLUDED');