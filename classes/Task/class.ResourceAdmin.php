<?php

namespace ILIAS\Plugin\LongEssayAssessment\Task;

use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\ResourceStorage\Identification\ResourceIdentification;

class ResourceAdmin
{
    /**
     * @var int
     */
    protected $task_id;

    /**
     * @param int $a_task_id
     */
    public function __construct(int $a_task_id)
    {
        $this->task_id = $a_task_id;
    }

    /**
     * @param string $a_title
     * @param string $a_description
     * @param string $a_availability
     * @param UploadResult $a_upload
     * @param int $a_user_id
     * @return int
     */
    public function saveFileResource(string $a_title, string $a_description, string $a_availability, string $identification): int
    {

        $resource = new Resource();
        $resource->setType(Resource::RESOURCE_TYPE_FILE);
        $resource->setTitle($a_title);
        $resource->setDescription($a_description);
        $resource->setAvailability($this->validateAvailability($a_availability));
        $resource->setTaskId($this->getTaskId());
        $resource->setFileId($identification);

        $let_dic = LongEssayAssessmentDI::getInstance();
        $task_repo = $let_dic->getTaskRepo();
        $task_repo->save($resource);
        return $resource->getId();
    }

    /**
     * @param string $a_title
     * @param string $a_description
     * @param string $a_availability
     * @param string $a_url
     * @return int
     */
    public function saveURLResource(string $a_title, string $a_description, string $a_availability, string $a_url): int
    {
        $resource = new Resource();
        $resource->setType(Resource::RESOURCE_TYPE_URL);
        $resource->setTitle($a_title);
        $resource->setDescription($a_description);
        $resource->setAvailability($this->validateAvailability($a_availability));
        $resource->setTaskId($this->getTaskId());
        $resource->setUrl($this->normalizeUrl($a_url));

        $let_dic = LongEssayAssessmentDI::getInstance();
        $task_repo = $let_dic->getTaskRepo();
        $task_repo->save($resource);

        return $resource->getId();
    }

    /**
     * @param int $a_id
     * @param string $a_title
     * @param string $a_description
     * @param string $a_availability
     * @param string $a_url
     * @return bool
     */
    public function updateResource(int $a_id, string $a_title, string $a_description, string $a_availability, string $a_url = ""): bool
    {
        $let_dic = LongEssayAssessmentDI::getInstance();
        $task_repo = $let_dic->getTaskRepo();
        $resource = $task_repo->getResourceById($a_id);

        if ($resource != null) {
            $resource->setTitle($a_title);
            $resource->setDescription($a_description);
            $resource->setAvailability($this->validateAvailability($a_availability));
            if ($resource->getType() == Resource::RESOURCE_TYPE_URL) {
                $resource->setUrl($this->normalizeUrl($a_url));
            }
            $task_repo->save($resource);
            return true;
        }

        return false;
    }

    /**
     * @param int $a_id
     * @param int $a_user_id
     * @param UploadResult $a_upload
     * @return bool
     */
    public function updateResourceFile(int $a_id, int $a_user_id, UploadResult $a_upload): bool
    {
        global $DIC;
        $let_dic = LongEssayAssessmentDI::getInstance();
        $task_repo = $let_dic->getTaskRepo();
        $resource = $task_repo->getResourceById($a_id);

        if ($resource != null && $resource->getType() == Resource::RESOURCE_TYPE_FILE) {
            $stakeholder = new ResourceResourceStakeholder($a_user_id);
            $identification = new ResourceIdentification($resource->getFileId());
            $DIC->resourceStorage()->manage()->replaceWithUpload($identification, $a_upload, $stakeholder);
            return true;
        }

        return false;
    }

    /**
     * @param int $a_id
     * @return \ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource
     */
    public function getResource(?int $a_id = 0): Resource
    {
        if ($a_id === null) {
            $resource = new Resource();
            $resource->setTaskId($this->getTaskId());
            return $resource;
        }

        $let_dic = LongEssayAssessmentDI::getInstance();
        $task_repo = $let_dic->getTaskRepo();
        $resource = $task_repo->getResourceById($a_id);


        return $resource;
    }

    public function deleteResource(?int $a_id = 0, ?bool $a_with_file = true): bool
    {
        global $DIC;
        $let_dic = LongEssayAssessmentDI::getInstance();
        $task_repo = $let_dic->getTaskRepo();
        $resource = $task_repo->getResourceById($a_id);
        if ($resource !== null) {
            $task_repo->deleteResource($a_id);

            if ($resource->getType() === Resource::RESOURCE_TYPE_FILE && $a_with_file) {
                $file_id = $resource->getFileId();
                $file = $DIC->resourceStorage()->manage()->find($file_id);
                if ($file !== null) {
                    $stakeholder = new ResourceResourceStakeholder($DIC->user()->getId());
                    $DIC->resourceStorage()->manage()->remove($file, $stakeholder);
                }
            }
            return true;
        }

        return false;
    }


    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return ResourceAdmin
     */
    public function setTaskId(int $task_id): ResourceAdmin
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     *
     * @param string $a_availability
     * @return string
     */
    protected function validateAvailability(string $a_availability): string
    {
        if (in_array(
            $a_availability,
            [Resource::RESOURCE_AVAILABILITY_AFTER,
                Resource::RESOURCE_AVAILABILITY_DURING,
                Resource::RESOURCE_AVAILABILITY_BEFORE]
        )
        ) {
            return $a_availability;
        }
        return Resource::RESOURCE_AVAILABILITY_AFTER;
    }

    /**
     * reformat a url so that it can be opened in the writer and corrector
     */
    protected function normalizeUrl(?string $a_url): string
    {
        $parsed = parse_url($a_url);

        if ($parsed === false) {
            return '';
        }

        // handle input like "www.ilias.de" or "www.ilias.de/download-ilias"
        if (empty($parsed['scheme']) && empty($parsed['host']) && empty($parsed['port'])) {
            if (!empty($parsed['path'])) {
                $slash = strpos($parsed['path'], '/');
                if ($slash === false) {
                    $parsed['host'] = $parsed['path'];
                    $parsed['path'] = '';
                } else {
                    $parsed['host'] = substr($parsed['path'], 0, $slash);
                    $parsed['path'] = substr($parsed['path'], $slash);
                }
            }
        }

        return (empty($parsed['scheme']) ? '//' : $parsed['scheme'] . '://')
            . (empty($parsed['host']) ? $_SERVER['SERVER_NAME'] : $parsed['host'])
            . (empty($parsed['port']) ? '' : ':' . $parsed['port'])
            . ($parsed['path'] ?? '')
            . (empty($parsed['query']) ? '' : '?' . $parsed['query'])
            . (empty($parsed['fragment']) ? '' : '#' . $parsed['fragment']);
    }
}
