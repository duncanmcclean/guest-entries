<!-- statamic:hide -->

![Banner](./banner.png)

## Guest Entries

<!-- /statamic:hide -->

Guest Entries allows your users to create, update & delete entries from the front-end of your site. Essentially [Workshop](https://statamic.com/addons/statamic/workshop) from the v2 days.

This repository contains the source code for Guest Entries. Guest Entries is a commercial addon, to use it in production, you'll need to [purchase a license](https://statamic.com/guest-entries).

### User contributed content

Let your users create and update your site's content, straight from the front-end. 

### File uploads

Allow your users to upload images or documents to your entries - on upload, they'll be saved as assets.

### Simple spam prevention

Guest Entries provides an optional honeypot you can add to your 'guest entry forms' to reduce spam.

## Installation

First, require Guest Entries as a Composer dependency:

```
composer require doublethreedigital/guest-entries
```

Once installed, you’ll want to publish the default configuration file.

```
php artisan vendor:publish --tag="guest-entries-config"
```

## Documentation

Read the documentation over on the [Statamic Marketplace](https://statamic.com/addons/double-three-digital/guest-entries/docs).

## Commercial addon

Guest Entries is a commercial addon - you **must purchase a license** via the [Statamic Marketplace](https://statamic.com/addons/double-three-digital/guest-entries) to use it in a production environment.

## Security

Only the latest version of Guest Entries (v1.0) will receive security updates if a vulnerability is found. 

If you discover a security vulnerability, please report it to Duncan straight away, [via email](mailto:security@doublethree.digital). Please don't report security issues through GitHub Issues.

## Official Support

If you're in need of some help with Guest Entries, [send me an email](mailto:help@doublethree.digital) and I'll do my best to help! (I'll usually respond within a day)

<!-- statamic:hide -->

---

<p>
<a href="https://statamic.com"><img src="https://img.shields.io/badge/Statamic-3.0+-FF269E?style=for-the-badge" alt="Compatible with Statamic v3"></a>
<a href="https://packagist.org/packages/doublethreedigital/guest-entries/stats"><img src="https://img.shields.io/packagist/v/doublethreedigital/guest-entries?style=for-the-badge" alt=":addonName on Packagist"></a>
</p>

<!-- /statamic:hide -->
