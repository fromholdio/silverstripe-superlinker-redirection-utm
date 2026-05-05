<?php

namespace Fromholdio\SuperLinkerRedirectionUTM\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ToggleCompositeField;
use UncleCheese\DisplayLogic\Forms\Wrapper;

class RedirectionSuperLinkUTMExtension extends Extension
{
    private static $db = [
        'UTMSource' => 'Varchar',
        'UTMMedium' => 'Varchar',
        'UTMCampaign' => 'Varchar',
    ];

    private static $enable_utm_parameters = true;

    public function updateURL(?string &$url): void
    {
        if (!$this->isEnabled() || empty($url) || !$this->isSupportedLinkType()) {
            return;
        }

        $params = $this->getUTMParameters();
        if (empty($params)) {
            return;
        }

        $url = Controller::join_links(
            $url,
            '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986)
        );
    }

    public function updateCMSLinkFields(FieldList $fields, string $fieldPrefix = ''): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $isReadonly = $this->isCMSFieldsReadonly();
        if ($isReadonly && !$this->isSupportedLinkType()) {
            return;
        }

        $utmFields = Wrapper::create(
            ToggleCompositeField::create(
                $fieldPrefix . 'UTMParameters',
                _t(__CLASS__ . '.UTMParameters', 'UTM tracking parameters'),
                [
                    $this->createUTMField(
                        $fieldPrefix . 'UTMSource',
                        _t(__CLASS__ . '.UTMSource', 'Source'),
                        $isReadonly
                    )->setDescription(_t(
                        __CLASS__ . '.UTMSourceDescription',
                        'Identifies the source of your traffic, such as newsletter, social, or a referring website.'
                    )),
                    $this->createUTMField(
                        $fieldPrefix . 'UTMMedium',
                        _t(__CLASS__ . '.UTMMedium', 'Medium'),
                        $isReadonly
                    )->setDescription(_t(
                        __CLASS__ . '.UTMMediumDescription',
                        'Identifies the marketing medium used, such as email, cpc, social, rss, or qrcode.'
                    )),
                    $this->createUTMField(
                        $fieldPrefix . 'UTMCampaign',
                        _t(__CLASS__ . '.UTMCampaign', 'Campaign'),
                        $isReadonly
                    )->setDescription(_t(
                        __CLASS__ . '.UTMCampaignDescription',
                        'Identifies a specific product promotion or campaign, such as summer_sale or promo.'
                    )),
                ]
            )
        );

        if (!$isReadonly) {
            $utmFields
                ->displayIf($fieldPrefix . 'LinkType')->isEqualTo('sitetree')
                ->orIf($fieldPrefix . 'LinkType')->isEqualTo('file');
        }

        $fields->push($utmFields);
    }

    public function onBeforeWrite(): void
    {
        $this->owner->setField('UTMSource', $this->normaliseUTMValue($this->owner->getField('UTMSource')));
        $this->owner->setField('UTMMedium', $this->normaliseUTMValue($this->owner->getField('UTMMedium')));
        $this->owner->setField('UTMCampaign', $this->normaliseUTMValue($this->owner->getField('UTMCampaign')));
    }

    protected function isEnabled(): bool
    {
        return (bool) $this->owner->config()->get('enable_utm_parameters');
    }

    protected function isSupportedLinkType(): bool
    {
        return in_array($this->owner->getField('LinkType'), ['sitetree', 'file'], true);
    }

    protected function isCMSFieldsReadonly(): bool
    {
        return $this->owner->hasMethod('isCMSFieldsReadonly')
            && (bool) $this->owner->isCMSFieldsReadonly();
    }

    protected function getUTMParameters(): array
    {
        $fields = [
            'utm_source' => 'UTMSource',
            'utm_medium' => 'UTMMedium',
            'utm_campaign' => 'UTMCampaign',
        ];

        $params = [];
        foreach ($fields as $param => $fieldName) {
            $value = $this->normaliseUTMValue($this->owner->getField($fieldName));
            if ($value !== '') {
                $params[$param] = $value;
            }
        }

        return $params;
    }

    protected function createUTMField(string $name, string $title, bool $isReadonly): FormField
    {
        if (!$isReadonly) {
            return TextField::create($name, $title);
        }

        $fieldName = (string) preg_replace('/^.*_/', '', $name);
        return ReadonlyField::create(
            $name . 'ReadOnly',
            $title,
            $this->owner->getField($fieldName)
        );
    }

    protected function normaliseUTMValue(mixed $value): string
    {
        return str_replace(' ', '_', trim((string) $value));
    }
}
