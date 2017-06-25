<?php
namespace App;

use LinkedinSdk\Helpers\Constants;
use App\Helpers\Constants as AppConfig;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use LinkedinSdk\ApiService;
use LinkedinSdk\Config\ServiceDescription;
use LinkedinSdk\Models\Request\Organizations;
use LinkedinSdk\Models\Request\SocialActions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Psr\Http\Message\RequestInterface;

class GenerateCommand extends Command
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('api:hit-endpoint')
            ->setDescription('Make the request to the linkeding endpoint.')
            ->setHelp('Enter the name of the operation from your service description that you want to be executed.')
            ->addArgument('operationName', InputArgument::REQUIRED, 'The operation name.')
        ;
    }
    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = $input->getArgument('operationName');

        switch ($operation) {
            case 'RetrieveSocial':
                $this->retrieveSocial($output, $operation);
                break;
            case 'FindOrganizationByVanityName':
                $this->findOrganizationByVanityName($output, $operation);
                break;
            case 'FindOrganizationByEmailDomain':
                $this->findOrganizationByEmailDomain($output, $operation);
                break;
            default:
                $output->writeln('Operation ' . $operation . ' not recognized.');
        }
    }

    /**
     * @param OutputInterface $output
     * @param $operation
     */
    protected function retrieveSocial(OutputInterface $output, $operation)
    {
        $serviceDescription = ServiceDescription::getAll();
        $requestParams = new SocialActions();
        $requestParams->setShareUrn('urn:li:activity:6281507220136034304');

        $results = (
        new ApiService($this->getClient(), $serviceDescription))
            ->getResult($operation, $requestParams);

        print_r($results);
    }


    /**
     * @param OutputInterface $output
     * @param $operation
     */
    protected function findOrganizationByVanityName(OutputInterface $output, $operation)
    {
        $serviceDescription = ServiceDescription::getAll();
        $requestParams = new Organizations();
        $requestParams->setQ('vanityName');
        $requestParams->setVanityName('quintly');

        $results = (
        new ApiService($this->getClient(), $serviceDescription))
            ->getResult($operation, $requestParams);
    }

    /**
     * @param OutputInterface $output
     * @param $operation
     */
    protected function findOrganizationByEmailDomain(OutputInterface $output, $operation)
    {
        $serviceDescription = ServiceDescription::getAll();
        $requestParams = new Organizations();
        $requestParams->setQ('emailDomain');
        $requestParams->setEmailDomain('quintly.com');

        $results = (
        new ApiService($this->getClient(), $serviceDescription))
            ->getResult($operation, $requestParams);
    }

    public function accessTokenMiddleware($accessToken)
    {
        return function (callable $handler) use ($accessToken) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler, $accessToken) {
                $request = $request->withHeader('Authorization', 'Bearer ' . $accessToken);
                return $handler($request, $options);
            };
        };
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push($this->accessTokenMiddleware(AppConfig::QUINTLY_ACCESS_TOKEN));

        return new Client(['handler' => $stack]);
    }
}