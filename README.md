# silverstripe-superlinker-redirection-utm

Adds optional UTM tracking parameters to sitetree and file targets in `fromholdio/silverstripe-superlinker-redirection`.

## Requirements

- PHP 8.4+
- Silverstripe CMS 5.2 or 6
- `fromholdio/silverstripe-superlinker-redirection` 3 or 4

## Installation

```sh
composer require fromholdio/silverstripe-superlinker-redirection-utm
```

Run `dev/build` after installation so the UTM fields are added to redirection records.

## Usage

The module adds a "UTM tracking parameters" field group to redirection links whose target type is "Page on this website" or "Download a file". Entered values are appended to the generated destination URL as:

- `utm_source`
- `utm_medium`
- `utm_campaign`

Values are trimmed with `mb_trim()` and spaces are converted to underscores before being stored. Query parameter values are encoded before being appended to the URL.

## Configuration

UTM fields and URL output are enabled by default. To disable them:

```yml
Fromholdio\SuperLinkerRedirection\Model\RedirectionSuperLink:
  enable_utm_parameters: false
```
