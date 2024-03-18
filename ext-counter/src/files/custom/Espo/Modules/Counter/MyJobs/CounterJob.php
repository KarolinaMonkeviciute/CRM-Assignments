<?php
namespace Espo\Modules\Counter\MyJobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\ORM\EntityManager;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Espo\ApiClient\Client;
use Espo\ApiClient\Exception\ResponseError; 

class CounterJob implements Job
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function run(Data $data): void
    {
        $path = dirname(__DIR__).'/../../../../';
        $size = $this->getDirectorySize($path);
        $diskSize = $this->getDiskSize();
        $records = $this->countRecords();
        $userCount = $this->getUserCount();
        $this->createEntity($diskSize, $size, $records, $userCount);
        $this->sendApi($diskSize, $size, $records, $userCount);
    }

    private function getDirectorySize(string $path): float {
        $bytestotal = 0;
        $path = realpath($path);
        if($path!==false && $path!='' && file_exists($path)){
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
                $bytestotal += $object->getSize();
            }
        }
        return $bytestotal / 1024 / 1024;
    }

    private function getDiskSize(): float {
        $diskSize = 0;
        $os = php_uname('s');
        if($os === 'Windows NT'){
            $dDisk = disk_total_space("D:");
            if($dDisk !== false){
                $diskSize += $dDisk;
            }
            $diskSize += disk_total_space("C:");
        }else{
            $diskSize += disk_total_space("/");
        }
        return $diskSize / 1024 / 1024;
    }

    private function countRecords(): int {
        $stmt = $this->entityManager->getSqlExecutor()->execute("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
        $stmt->execute();
        $tables = $stmt->fetchAll();
        $totalRecords = 0;

        foreach ($tables as $table) {
            $stmt = $this->entityManager->getSqlExecutor()->execute("SELECT COUNT(*) as count FROM `$table[0]`");
            $stmt->execute();
            $result = $stmt->fetch();
            $totalRecords += $result['count'];
        }

        return $totalRecords;
    }

    private function getUserCount(): int {
        return $this->entityManager->getRDBRepository('User')->count();
    }

    private function generateRandomString(int $length = 10): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function createEntity(mixed $diskSize, mixed $size, mixed $records, mixed $userCount): void {
        $entity = $this->entityManager->getEntity('Counter');
        $entity->set('name', $this->generateRandomString());
        $entity->set('diskSize', $diskSize);
        $entity->set('size', $size);
        $entity->set('numberOfRecords', $records);
        $entity->set('nuberOfUsers', $userCount);
        try {
            $this->entityManager->saveEntity($entity);
            echo "Entity saved successfully.";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    private function sendApi(mixed $diskSize, mixed $size, mixed $records, mixed $userCount): void {
        $integrationEntity = $this->entityManager->getEntityById('Integration', 'CounterIntegration');
        $integrationEntityData = $integrationEntity->getValueMap();
        $apiKey = $integrationEntityData->apiKey;
        $secretKey = $integrationEntityData->secretKey;
        $destinationUrl = $integrationEntityData->destinationUrl;

        if($destinationUrl && $apiKey && $secretKey) {
            $client = new Client($destinationUrl);
            $client->setApiKey($apiKey);
            $client->setSecretKey($secretKey); 

            try {
                $response = $client->request(Client::METHOD_POST, 'Counter', [
                    'name' => $this->generateRandomString(),
                    'diskSize' => $diskSize,
                    'size' => $size,
                    'numberOfRecords' => $records,
                    'nuberOfUsers' => $userCount
                ]);
                
                echo 'Successfully created entity via api, id: ' . $response->getParsedBody()->id;
            } catch(ResponseError $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
    
}


