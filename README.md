# MyAdmin KVM VPS Plugin

[![Build Status](https://github.com/detain/myadmin-kvm-vps/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-kvm-vps/actions)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-kvm-vps/version)](https://packagist.org/packages/detain/myadmin-kvm-vps)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-kvm-vps/downloads)](https://packagist.org/packages/detain/myadmin-kvm-vps)
[![License](https://poser.pugx.org/detain/myadmin-kvm-vps/license)](https://packagist.org/packages/detain/myadmin-kvm-vps)

A MyAdmin plugin for managing KVM (Kernel-based Virtual Machine) virtual private servers. This package provides service lifecycle management including activation, deactivation, queue processing, and administrative settings for KVM-based VPS hosting across multiple datacenter locations.

## Features

- Service activation and deactivation handling for KVM Linux and Windows VPS
- Queue-based provisioning through Smarty shell templates
- Administrative settings for slice pricing and server assignment
- Per-datacenter out-of-stock controls (Secaucus, Los Angeles, Texas)
- Support for standard KVM, Cloud KVM, KVMv2, and KVM Storage types
- Symfony EventDispatcher integration for hook-based architecture

## Requirements

- PHP >= 5.0
- ext-soap
- symfony/event-dispatcher ^5.0

## Installation

```sh
composer require detain/myadmin-kvm-vps
```

## Usage

The plugin registers event hooks automatically through the MyAdmin plugin system. Call `Plugin::getHooks()` to retrieve the array of event name to callback mappings:

```php
use Detain\MyAdminKvm\Plugin;

$hooks = Plugin::getHooks();
// Returns: ['vps.settings' => [...], 'vps.deactivate' => [...], 'vps.queue' => [...]]
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html) license.
