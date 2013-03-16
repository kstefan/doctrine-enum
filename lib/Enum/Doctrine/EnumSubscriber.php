<?php

namespace Enum\Doctrine;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Enum\Doctrine\Mapping\Annotation\Enum;
use Enum\EnumInterface;

class EnumSubscriber implements EventSubscriber
{
    const CACHE_PREFIX = 'KSTEFANENUM';

    protected static $enumTypes = array(
        'string_enum',
    );

    protected $enumMetadataCache = array();

    /**
     * @var \Doctrine\Common\Annotations\CachedReader
     */
    protected $annotationReader;

    public function __construct()
    {
        $this->annotationReader = new CachedReader(new AnnotationReader(), new ArrayCache());

        Type::addType('string_enum', 'Enum\Doctrine\Type\StringEnumType');
    }

    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata',
            'postLoad',
        );
    }

    protected function getCacheKey($entityName)
    {
        return self::CACHE_PREFIX . $entityName;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();
        $cacheKey = $this->getCacheKey($metadata->getName());

        if (!isset($this->enumMetadataCache[$cacheKey])) {
            $cache = $eventArgs->getEntityManager()->getConfiguration()->getMetadataCacheImpl();

            if (!$cache->contains($cacheKey)) {
                $enums = array();
                foreach ($metadata->getFieldNames() as $field) {
                    if (in_array($metadata->getTypeOfField($field), self::$enumTypes)) {
                        $reflProp = $metadata->getReflectionClass()->getProperty($field);

                        foreach ($this->annotationReader->getPropertyAnnotations($reflProp) as $annotation) {
                            if ($annotation instanceof Enum) {
                                $enums[$field] = $annotation;
                            }
                        }
                    }
                }

                $cache->save($cacheKey, $enums);
            }

            $this->enumMetadataCache[$cacheKey] = $cache->fetch($cacheKey);
        }
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $cacheKey = $this->getCacheKey(get_class($entity));

        if (isset($this->enumMetadataCache[$cacheKey])) {
            foreach ($this->enumMetadataCache[$cacheKey] as $field => $annotation) {
                $getter = 'get' . ucfirst($field);

                if (!method_exists($entity, $getter)) {
                    throw new RuntimeException('Getter "' . $getter . '" not defined.');
                }

                $value = $entity->$getter();
                if (!$value instanceof EnumInterface && !is_null($value)) {
                    $setter = 'set' . ucfirst($field);

                    if (!method_exists($entity, $setter)) {
                        throw new RuntimeException('Setter "' . $setter . '" not defined.');
                    }

                    $class = $annotation->class;
                    $entity->$setter(new $class($value));
                }
            }
        }
    }


}
