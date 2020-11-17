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

use Metallike\Component\DependencyInjection\Exception\ContainerException;
use Metallike\Component\DependencyInjection\Exception\InvalidArgumentException;
use Metallike\Component\DependencyInjection\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;

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
    protected $parameters = [];
    protected $lockedParameters = [];

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
            throw new InvalidArgumentException(sprintf('You cannot set service "%s" because it is a protected name.', self::DEFAULT_CONTAINER_ID));
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
        }

        if (null === $service) {
            throw new InvalidArgumentException(sprintf('You cannot unset an undefined service "%s".', $id));
        }

        $this->add($id, $service, $lock);
    }

    /**
     * Sets a parameter.
     *
     * @param string $id    The ID (name) of the parameter.
     * @param mixed  $value The value of the parameter.
     * @param bool   $lock
     *
     * @throws InvalidArgumentException
     */
    public function setParameter(string $id, $value, bool $lock = false)
    {
        if ($this->hasParameter($id)) {
            if ($this->isLockedParameter($id)) {
                throw new InvalidArgumentException(sprintf('The parameter "%s" is locked, you cannot replace or unset it.', $id));
            } elseif (null === $value) {
                $this->removeParameter($id);
            } else {
                $this->updateParameter($id, $value, $lock);
            }

            return;
        }

        if (null === $value) {
            throw new InvalidArgumentException(sprintf('You cannot unset an undefined parameter "%s".', $id));
        }

        $this->addParameter($id, $value, $lock);
    }

    /**
     * Returns a service.
     *
     * @param string $id
     *
     * @return mixed|object
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Service "%s" not found.', $id));
        }

        return $this->resolve($id);
    }

    /**
     * Returns a parameter.
     *
     * @param string $id
     *
     * @return mixed
     * @throws NotFoundException
     */
    public function getParameter(string $id)
    {
        if (!$this->hasParameter($id)) {
            throw new NotFoundException(sprintf('Parameter "%s" not found.', $id));
        }

        return $this->parameters[$id];
    }

    /**
     * Returns true if the service is defined.
     *
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
     * Returns true if the parameter is defined.
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasParameter(string $id): bool
    {
        if (isset($this->parameters[$id])) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the service is locked.
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
     * Returns true if the parameter is locked.
     *
     * @param string $id
     *
     * @return bool
     */
    private function isLockedParameter(string $id): bool
    {
        if ($this->lockedParameters[$id]) {
            return true;
        }

        return false;
    }

    /**
     * Defines the service.
     *
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
     * Defines the parameter.
     *
     * @param string $id
     * @param        $value
     * @param bool   $lock
     */
    private function addParameter(string $id, $value, bool $lock)
    {
        $this->parameters[$id] = $value;
        $this->lockedParameters[$id] = $lock;
    }

    /**
     * Updates the service.
     *
     * @param string $id
     * @param string $service
     * @param bool   $lock
     */
    private function update(string $id, string $service, bool $lock)
    {
        $this->add($id, $service, $lock);
    }

    /**
     * Updates the parameter.
     *
     * @param string $id
     * @param        $value
     * @param bool   $lock
     */
    private function updateParameter(string $id, $value, bool $lock)
    {
        $this->addParameter($id, $value, $lock);
    }

    /**
     * Removes a service from the container.
     *
     * @param string $id
     */
    private function remove(string $id)
    {
        unset($this->services[$id]);
        unset($this->lockedServices[$id]);
    }

    /**
     * Removes a parameter from the container.
     *
     * @param string $id
     */
    private function removeParameter(string $id)
    {
        unset($this->parameters[$id]);
        unset($this->lockedParameters[$id]);
    }

    /**
     * Resolves a single service.
     *
     * @param string $id
     *
     * @return object
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function resolve(string $id)
    {
        if (!class_exists($this->services[$id])) {
            throw new NotFoundException(sprintf('Service "%s" does not exist.', $this->services[$id]));
        }

        $reflector = new ReflectionClass($this->services[$id]);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException(sprintf('Service "%s" is not instantiable', $this->services[$id]));
        }

        $constructor = $reflector->getConstructor();

        if (null === $constructor) {
            return $reflector->newInstance();
        }

        $constructorParameters = $constructor->getParameters();
        $constructorDependencies = $this->getDependencies($constructorParameters);

        return $reflector->newInstanceArgs($constructorDependencies);
    }

    /**
     * @param $parameters
     *
     * @return array
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private function getDependencies($parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if (null === $dependency) {
                if (!$parameter->isDefaultValueAvailable()) {
                    throw new ContainerException(sprintf('Cannot resolve class dependency "%s".', $parameter->name));
                }

                $dependencies[] = $parameter->getDefaultValue();
            } else {
                $dependencies[] = $this->get(array_search($dependency->name, $this->services));
            }
        }

        return $dependencies;
    }
}