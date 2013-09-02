<?php

namespace ZfJoacubUploaderTwb\Service;

use Zend\ServiceManager\FactoryInterface;
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
}