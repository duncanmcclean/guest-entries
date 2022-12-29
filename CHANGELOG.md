# Changelog

## Unreleased

## v2.0.0 (2022-12-29)

The supported versions of PHP/Statamic/Laravel used alongside this addon have changed, the supported versions are now:

- PHP 8.1 & 8.2
- Statamic 3.3
- Laravel 9

## v1.2.3 (2022-10-17)

### What's fixed

- Avoid entries being saved twice #30 #31

## v1.2.2 (2022-10-03)

### What's fixed

- Fixed assets not being visible in Control Panel after file upload when the Stache watcher has been disabled #28 #29

## v1.2.1 (2022-05-04)

### What's fixed

- Fixed an issue with file uploads where the path would be duplicated #24 #25

## v1.2.0 (2022-02-26)

### What's new

- Statamic 3.3 support

### Breaking changes

- Dropped support for Statamic 3.0 and Statamic 3.1

## v1.1.1 (2021-12-31)

Same as [v1.1.0](https://github.com/duncanmcclean/guest-entries/releases/tag/v1.1.0)

## v1.1.0 (2021-12-31)

### What's new

- PHP 8.1 Support #21

## v1.0.8 (2021-10-16)

### What's new

- Guest Entries now supports multi-site #18

### What's improved

- Improved date handling #17

## v1.0.7 (2021-09-27)

### What's fixe

- Fixed PSR-4 autoloading issue #11

## v1.0.6 (2021-09-24)

### What's new

- File uploads now support uploading multiple files #10

### What's fixed

- File uploads will now include a timestamp in the saved filename #10

## v1.0.5 (2021-09-21)

### What's fixed

- Possibly fixed the file uploads issue experienced in #9

## v1.0.4 (2021-09-13)

### What's new

- A 'working copy' revision will be created on entry update if collection has revisions enabled. #4

## v1.0.3 (2021-09-11)

### What's new

- File Uploads #1

### What's fixed

- The CSRF token is no longer saved as data on entries
- Publish Dates are now saved correctly if you're using a dated collection (and you provide a date) #6

## v1.0.2 (2021-09-06)

### What's new

- [Events](https://github.com/duncanmcclean/guest-entries#events)
- Added tag for [error handing](https://github.com/duncanmcclean/guest-entries#events) #3

## v1.0.1 (2021-09-04)

### What's new

- Entries are now unpublished by default (instead of being published straight away)
- You can now change the published state, just use an input with the name `published`

### What's fixed

- The entry data passed into the update/delete tags is now raw, not augmented.

## v1.0.0 (2021-09-03)

- Initial release
