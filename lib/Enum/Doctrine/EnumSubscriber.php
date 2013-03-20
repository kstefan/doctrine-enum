<?php

namespace Enum\Doctrine;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Enum\Doctrine\Mapping\Annotation\Enum;
use Enum\EnumInterface;

class EnumSubscriber implements EventSubscriber
{
    const CACHE_PREFIX = 'KSTEFANENUM';

    protected static $enumTypes = [
        'string_enum',
    ];

    protected $enumMetadataCache = [];

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

    /**
     * @param EntityManager $entityManager
     * @return \Doctrine\Common\Cache\Cache
     */
    protected function getCache(EntityManager $entityManager)
    {
        return $entityManager->getConfiguration()->getMetadataCacheImpl();
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();
        $cacheKey = $this->getCacheKey($metadata->getName());
        $cache = $this->getCache($eventArgs->getEntityManager());

        $enums = [];
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

        if (count($enums)) {
            $cache->save($cacheKey, $enums);
        }
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $cacheKey = $this->getCacheKey(get_class($entity));

        if (!array_key_exists($cacheKey, $this->enumMetadataCache)) {
            $cache = $this->getCache($eventArgs->getEntityManager());
            $this->enumMetadataCache[$cacheKey] = $cache->contains($cacheKey)
                ? $cache->fetch($cacheKey)
                : []
            ;
        }

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
