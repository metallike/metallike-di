<?php
/*
 * This file is part of the Metallike Framework package.
 *
 * (c) Florian Brandl <fb@metallike.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Metallike\Component\DependencyInjection;

use Metallike\Component\DependencyInjection\Exception\InvalidArgumentException;
use Metallike\Component\DependencyInjection\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * A psr-11 compliant dependency injection container.
 *
 * @author Florian Brandl <fb@metallike.de>
 */
class Container implements ContainerInterface
{
    /**
     * Protected designation of the service container
     */
    const DEFAULT_CONTAINER_ID = 'service_container';

    protected $services = [];
    protected $lockedServices = [];

    /**
     * Sets a service.
     *
     * @param string      $id      The ID (name) of the service
     * @param string|null $service The service instance
     * @param bool        $lock    The lock status
     *
     * @throws InvalidArgumentException
     */
    public function set(string $id, ?string $service, $lock = false)
    {
        // abort if service id eq default container id
        if (self::DEFAULT_CONTAINER_ID === $id) {
            throw new InvalidArgumentException(sprintf('You cannot set service "%s".', self::DEFAULT_CONTAINER_ID));
        }

        // update or unset service if id already exists
        // throws an exception if you want to unset a non-existing service
        if ($this->has($id)) {
            if ($this->isLocked($id)) {
                throw new InvalidArgumentException(sprintf('The service "%s" is locked, you cannot replace or unset it.', $id));
            } elseif (null === $service) {
                $this->remove($id);
            } else {
                $this->update($id, $service, $lock);
            }

            return;
        } else {
            if (null === $service) {
                throw new InvalidArgumentException(sprintf('You cannot unset an undefined service "%s".', $id));
            }
        }

        $this->add($id, $service, $lock);
    }

    /**
     * @param string $id
     *
     * @return mixed|string
     */
    public function get(string $id)
    {
        return $this->resolve($id);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        if (isset($this->services[$id])) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a service is locked.
     *
     * @param string $id
     *
     * @return bool
     */
    private function isLocked(string $id): bool
    {
        if ($this->lockedServices[$id]) {
            return true;
        }

        return false;
    }

    /**
     * @param string $id
     * @param string $service
     * @param bool   $lock
     */
    private function add(string $id, string $service, bool $lock)
    {
        $this->services[$id] = $service;
        $this->lockedServices[$id] = $lock;
    }

    /**
     * @param string $id
     * @param string $service
     * @param bool   $lock
     */
    private function update(string $id, string $service, bool $lock)
    {
        $this->add($id, $service, $lock);
    }

    /**
     * Remove a service from the container.
     *
     * @param string $id
     */
    private function remove(string $id)
    {
        unset($this->services[$id]);
        unset($this->lockedServices[$id]);
    }

    private function resolve(string $id)
    {
        try {
            $reflector = new ReflectionClass($this->services[$id]);
        } catch (\ReflectionException $e) {
            throw new NotFoundException(sprintf('Service "%s" does not exist.', $this->services[$id]));
        }

        if (!$reflector->isInstantiable()) {
            throw new \Exception(sprintf('Service "%s" is not instantiable', $this->services[$id]));
        }

        $constructor = $reflector->getConstructor();

        if (null === $constructor) {
            return $reflector->newInstance();
        }

        $constructorParameters = $constructor->getParameters();
        $constructorDependencies = $this->getDependencies($constructorParameters);

        return $reflector->newInstanceArgs($constructorDependencies);
    }

    private function getDependencies($parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if (null === $dependency) {
                if (!$parameter->isDefaultValueAvailable()) {
                    throw new \Exception(sprintf('Cannot resolve class dependency "%s".', $parameter->name));
                }

                $dependencies[] = $parameter->getDefaultValue();
            } else {
                $dependencies[] = $this->get(array_search($dependency->name, $this->services));
            }
        }

        return $dependencies;
    }
}