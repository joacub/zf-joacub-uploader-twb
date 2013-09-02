<?php
/**
* file: Module.php
* ZfJoacubUploaderTwb Module
*
* @author Zend Model Creator 2, [https://github.com/hussfelt/Zend-Model-Creator-2]
* @version 0.0.1
* @package ZfJoacubUploaderTwb
* @package ZfJoacubUploaderTwb
* @since 2012-12-28
*/

namespace ZfJoacubUploaderTwb;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\EventManager\EventInterface;
use Zend\Mvc\MvcEvent;
use Nette\Diagnostics\Debugger;
use ZfJoacubUploaderTwb\View\Helper\ZfJoacubUploaderTwb;
use Zend\ServiceManager\ServiceManager;
use Zend\Di\ServiceLocator;

/**
* Module
*
* @author Zend Model Creator 2, [https://github.com/hussfelt/Zend-Model-Creator-2]
* @version 0.0.1
* @package ZfJoacubUploaderTwb
* @since 2012-12-28
*
**/
class Module implements AutoloaderProviderInterface
{
    
    protected static $modulemanager;

	/**
	* getAutoLoaderConfig
	*
	**/
	public function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\ClassMapAutoloader' => array(
				__DIR__ . '/autoload_classmap.php',
			),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					// if we're in a namespace deeper than one level we need to fix the \ in the path
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				),
			),
		);
	}

	/**
	* getConfig
	*
	**/
	public function getConfig()
	{
		return include __DIR__ . '/config/module.config.php';
	}
	
	public function getServiceConfig()
	{
	    return array(
	        'factories' => array(
	            'ZfJoacubUploaderTwb' => 'ZfJoacubUploaderTwb\Service\Factory',
	        )
	    );
	}
	
	public function getViewHelperConfig()
	{
		/**
		 * @todo aun no funciona correctamente
		 */
		return array(
			'factories' => array(
				'ZfJoacubUploaderTwb' => function (ServiceManager $sm) {
					$locator = $sm->getServiceLocator();
					
					$locator instanceof ServiceLocator;
					
					$event = $locator->get('application')->getMvcEvent();
					
					$viewHelper = new ZfJoacubUploaderTwb();
					$viewHelper->setService($locator->get('ZfJoacubUploaderTwb'))->setEvent($event);
	
					return $viewHelper;
				},
			),
		);
	
	}
}