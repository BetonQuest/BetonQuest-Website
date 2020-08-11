<?php


namespace App\ApiPlatform;


use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class AutoGroupResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private const ACTION_READ = 'read';
    private const ACTION_WRITE = 'write';
    private const SCOPE_ITEM = 'item';
    private const SCOPE_COLLECTION = 'collection';

    /**
     * @var ResourceMetadataFactoryInterface
     */
    private ResourceMetadataFactoryInterface $decorated;

    /**
     * AutoGroupResourceMetadataFactory constructor.
     * @param ResourceMetadataFactoryInterface $decorated
     */
    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @param string $resourceClass
     * @return ResourceMetadata
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);

        $itemOperations = $resourceMetadata->getItemOperations();
        $resourceMetadata = $resourceMetadata->withItemOperations(
            $this->updateContextOnOperations($itemOperations, $resourceMetadata->getShortName(), self::SCOPE_ITEM)
        );
        $collectionOperations = $resourceMetadata->getCollectionOperations();
        $resourceMetadata = $resourceMetadata->withCollectionOperations(
            $this->updateContextOnOperations($collectionOperations, $resourceMetadata->getShortName(), self::SCOPE_COLLECTION)
        );

        return $resourceMetadata;
    }

    private function updateContextOnOperations(array $operations, string $element, string $scope)
    {
        $element = strtolower($element);
        foreach ($operations as $operationName => $operationOptions) {
            $operationOptions['normalization_context'] = $operationOptions['normalization_context'] ?? [];
            $operationOptions['normalization_context']['groups'] = $operationOptions['normalization_context']['groups'] ?? [];
            $operationOptions['normalization_context']['groups'] = array_unique(array_merge(
                $operationOptions['normalization_context']['groups'],
                $this->getDefaultGroups($element, $scope, self::ACTION_READ, $operationName)
            ));
            $operationOptions['denormalization_context'] = $operationOptions['denormalization_context'] ?? [];
            $operationOptions['denormalization_context']['groups'] = $operationOptions['denormalization_context']['groups'] ?? [];
            $operationOptions['denormalization_context']['groups'] = array_unique(array_merge(
                $operationOptions['denormalization_context']['groups'],
                $this->getDefaultGroups($element, $scope, self::ACTION_WRITE, $operationName)
            ));
            $operations[$operationName] = $operationOptions;
        }
        return $operations;
    }

    /**
     * @param string $element the elements (short) name
     * @param string $scope item or collection, but could be any other scope
     * @param string $action read or write, but could be any abstract action
     * @param string $operation HTTP operation like get, push or delete
     * @return string[] array with all relevant groups for given arguments
     */
    private function getDefaultGroups(string $element, string $scope, string $action, string $operation): array
    {
        return [
            $this->getGlobalActionGroup($element, $action),
            $this->getScopedActionGroup($element, $scope, $action),
            $this->getOperationGroup($element, $scope, $operation),
        ];
    }

    /**
     * Generates a global (unscoped) action group. The group follows the convention {element}:{action}, e.g. user:read.
     *
     * @param string $element the elements (short) name
     * @param string $action read or write, but could be any abstract action
     * @return string the generated global action group
     */
    private function getGlobalActionGroup(string $element, string $action): string
    {
        return sprintf('%s:%s', $element, $action);
    }

    /**
     * Generates a scoped action group. The group follows the convention {element}:{scope}:{action}, e.g. user:collection:read.
     *
     * @param string $element the elements (short) name
     * @param string $scope item or collection, but could be any other scope
     * @param string $action read or write, but could be any abstract action
     * @return string the generated scoped action group
     */
    private function getScopedActionGroup(string $element, string $scope, string $action): string
    {
        return sprintf('%s:%s:%s', $element, $scope, $action);
    }

    /**
     * Generates an operation group. The group follows the convention {element}:{scope}:{operation}, e.g. user:collection:get.
     *
     * @param string $element the elements (short) name
     * @param string $scope item or collection, but could be any other scope
     * @param string $operation HTTP operation like get, push or delete
     * @return string the generated operation group
     */
    private function getOperationGroup(string $element, string $scope, string $operation): string
    {
        return sprintf('%s:%s:%s', $element, $scope, $operation);
    }
}