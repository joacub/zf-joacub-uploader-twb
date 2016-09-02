<?php

namespace ZfJoacubUploaderTwb\Service;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfJoacubUploaderTwb\Manager;

/**
 * FileBank service manager factory
 */
class Factory implements FactoryInterface
{
    /**
     * Factory method for FileBank Manager service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return \ZfJoacubUploaderTwb\Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator) {
        $manager = new Manager($serviceLocator);
        return $manager;
    }

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return $this->createService($container);
    }


}