**Note:** Due to the fast changing ecosystem and better javascript support in popular frameworks, this lib has been deprecated with no direct alternative.

----

<p align="center"><a href="http://www.hostnet.nl" target="_blank">
    <img width="400" src="https://www.hostnet.nl/images/hostnet.svg">
</a></p>

# Asset Bundle
[![Build Status](https://github.com/hostnet/asset-bundle/actions/workflows/main.yml/badge.svg)](https://github.com/hostnet/asset-bundle/actions/workflows/main.yml)

This bundle exposes the [asset-lib](https://github.com/hostnet/asset-lib) as a Symfony bundle.

Installation
------------
Installation of the bundle can be done using `composer` and is the recommended way of adding the bundle to your application. 
To do so, in your command line enter the project directory and execute the following command to download the latest stable version of this bundle:

```bash
$ composer require hostnet/asset-bundle
```

This command requires you to have Composer installed globally, as explained in the [installation chapter](https://getcomposer.org/doc/00-intro.md) of the Composer documentation.

This bundle and the component follow [semantic versioning](http://semver.org/) strictly.

Documentation
-------------
Create a file called `hostnet_asset.yaml` in `config/packages` to configure this bundle.

## Node
In order for this bundle to work properly, it needs to know the location where `node` is installed and where the `node_modules` directory is located.

## Example configuration
```yaml
hostnet_asset:
    web_root: public
    source_root: assets
    files:
        - main.ts
    assets:
        - shims.js
    plugins:
        Hostnet\Component\Resolver\Plugin\CorePlugin: ~
        Hostnet\Component\Resolver\Plugin\LessPlugin: ~
        Hostnet\Component\Resolver\Plugin\TsPlugin: ~
        Hostnet\Component\Resolver\Plugin\CssFontRewitePlugin: ~
        Hostnet\Component\AssetAngularPlugin\MiscPlugin: ~
    bin:
        node: '%kernel.project_dir%/node_modules/.bin/node'
        node_modules: '%kernel.project_dir%/node_modules'
```

License
-------
The `hostnet/asset-bundle` is licensed under the [MIT License](https://github.com/hostnet/asset-bundle/blob/master/LICENSE), meaning you can reuse the code within proprietary software provided that all copies of the licensed software include a copy of the MIT License terms and the copyright notice.

Get in touch
------------
You can reach us via email: opensource@hostnet.nl.
