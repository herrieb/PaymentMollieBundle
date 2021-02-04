<?php

namespace Herrieb\Payment\MollieBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class HerriebPaymentMollieExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('herrieb_payment_mollie.api_key', $config['api_key']);

        foreach($config['methods'] AS $method) {
            $this->addFormType($config, $container, $method);
        }

        /**
         * When logging is disabled, remove logger and setLogger calls
         */
        if(false === $config['logger']) {
            $container->getDefinition('herrieb_payment_mollie.controller.notification')->removeMethodCall('setLogger');
            $container->getDefinition('herrieb_payment_mollie.plugin.default')->removeMethodCall('setLogger');
            $container->getDefinition('herrieb_payment_mollie.plugin.ideal')->removeMethodCall('setLogger');
            $container->removeDefinition('monolog.logger.herrieb_payment_mollie');
        }
    }

    protected function addFormType(array $config, ContainerBuilder $container, $method)
    {
        $mollieMethod = 'mollie_' . $method;

        $definition = new Definition();
        $definition->setClass(sprintf('%%herrieb_payment_mollie.form.%s_type.class%%', $method));
        $definition->addArgument($mollieMethod);

        if($method === 'ideal') {
            $definition->addArgument('%herrieb_payment_mollie.ideal.issuers%');
        }

        $definition->addTag('payment.method_form_type');
        $definition->addTag('form.type', array(
            'alias' => $mollieMethod
        ));

        $container->setDefinition(
            sprintf('herrieb_payment_mollie.form.%s_type', $method),
            $definition
        );
    }
}
