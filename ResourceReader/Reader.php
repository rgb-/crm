<?php
namespace Oro\Bundle\UserBundle\ResourceReader;

use Symfony\Component\DependencyInjection\ContainerInterface;

use JMS\DiExtraBundle\Finder\PatternFinder;

class Reader
{
    const ACL_CLASS = 'Oro\Bundle\UserBundle\Annotation\Acl';

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $reader;

    public function __construct(ContainerInterface $container)
    {
        $this->kernel = $container->get('kernel');
        $this->container = $container;
        $this->reader = $container->get('annotation_reader');
    }

    /**
     * Return array tree with resources
     *
     * @return array
     */
    public function getResources()
    {
        $directories = $this->getScanDirectories();
        if (!$directories) {
            return array();
        }

        $finder = new PatternFinder(self::ACL_CLASS, '*.php');
        $files = $finder->findFiles($directories);
        foreach ($files as $index => $file) {
            if (strpos($file, 'Annotation') !== false || strpos($file, 'ResourceReader') !== false) {
                unset($files[$index]);
            }
        }

        return $this->findResources($files);
    }

    /**
     * Get array with resources from annotations
     *
     * @param array $files
     *
     * @return \Oro\Bundle\UserBundle\Annotation\Acl[]
     */
    private function findResources(array $files)
    {
        $aclResources = array();
        foreach ($files as $file) {
            $className = $this->getClassName($file);
            $reflection = new \ReflectionClass($className);
            //read annotations from class definition
            $classAcl = $this->reader->getClassAnnotation($reflection, self::ACL_CLASS);
            if (is_object($classAcl)) {
                $aclResources[$classAcl->getId()] = $classAcl;
            }
            //read annotations from methods
            foreach ($reflection->getMethods() as $reflectionMethod) {
                $acl = $this->reader->getMethodAnnotation($reflectionMethod, self::ACL_CLASS);
                if (is_object($acl)) {
                    $aclResources[$acl->getId()] = $acl;
                }
            }
        }

        return $aclResources;
    }

    /**
     * get dir array of bundles
     *
     * @return array
     */
    private function getScanDirectories()
    {
        $bundles = $this->kernel->getBundles();

        foreach ($bundles as $bundle) {
            if (strpos($bundle->getPath(), 'vendor') === false) {
                $directories[] = $bundle->getPath();
            }
        }

        return $directories;
    }

    /**
     * Only supports one namespaced class per file
     *
     * @throws \RuntimeException if the class name cannot be extracted
     *
     * @param string $filename
     *
     * @return string the fully qualified class name
     */
    private function getClassName($filename)
    {
        $src = file_get_contents($filename);

        if (!preg_match('/\bnamespace\s+([^;]+);/s', $src, $match)) {
            throw new \RuntimeException(sprintf('Namespace could not be determined for file "%s".', $filename));
        }
        $namespace = $match[1];

        if (!preg_match('/\bclass\s+([^\s]+)\s+(?:extends|implements|{)/s', $src, $match)) {
            throw new \RuntimeException(sprintf('Could not extract class name from file "%s".', $filename));
        }

        return $namespace . '\\' . $match[1];
    }
}
