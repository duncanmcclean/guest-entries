# Changelog

## Unreleased

## v1.0.8 (2021-10-16)

### What's new

* Guest Entries now supports multi-site #18

### What's improved

* Improved date handling #17

## v1.0.7 (2021-09-27)

### What's fixe

* Fixed PSR-4 autoloading issue #11

## v1.0.6 (2021-09-24)

### What's new

* File uploads now support uploading multiple files #10

### What's fixed

* File uploads will now include a timestamp in the saved filename #10

## v1.0.5 (2021-09-21)

### What's fixed

* Possibly fixed the file uploads issue experienced in #9

## v1.0.4 (2021-09-13)

### What's new

* A 'working copy' revision will be created on entry update if collection has revisions enabled. #4

## v1.0.3 (2021-09-11)

### What's new

* File Uploads #1

### What's fixed

* The CSRF token is no longer saved as data on entries
* Publish Dates are now saved correctly if you're using a dated collection (and you provide a date) #6

## v1.0.2 (2021-09-06)

### What's new

* [Events](https://github.com/doublethreedigital/guest-entries#events)
* Added tag for [error handing](https://github.com/doublethreedigital/guest-entries#events) #3

## v1.0.1 (2021-09-04)

### What's new

* Entries are now unpublished by default (instead of being published straight away)
* You can now change the published state, just use an input with the name `published`

### What's fixed

* The entry data passed into the update/delete tags is now raw, not augmented.

## v1.0.0 (2021-09-03)

* Initial release
