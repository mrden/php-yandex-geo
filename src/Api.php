<?php
namespace Yandex\Geo;

use Yandex\Geo\Exception\CurlError;
use Yandex\Geo\Exception\MapsError;
use Yandex\Geo\Exception\ServerError;

/**
 * Class Api
 * @package Yandex\Geo
 * @license The MIT License (MIT)
 * @see http://api.yandex.ru/maps/doc/geocoder/desc/concepts/About.xml
 */
class Api
{
    /** дом */
    const KIND_HOUSE = 'house';
    /** улица */
    const KIND_STREET = 'street';
    /** станция метро */
    const KIND_METRO = 'metro';
    /** район города */
    const KIND_DISTRICT = 'district';
    /** населенный пункт (город/поселок/деревня/село/...) */
    const KIND_LOCALITY = 'locality';
    /** русский (по умолчанию) */
    const LANG_RU = 'ru-RU';
    /** украинский */
    const LANG_UA = 'uk-UA';
    /** белорусский */
    const LANG_BY = 'be-BY';
    /** американский английский */
    const LANG_US = 'en-US';
    /** британский английский */
    const LANG_BR = 'en-BR';
    /** турецкий (только для карты Турции) */
    const LANG_TR = 'tr-TR';
    /**
     * @var string Версия используемого api
     */
    protected string $_version = '1.x';

    protected array $_filters = [];

    protected ?Response $_response;

    public function __construct(string $version = null)
    {
        $this->_version = $version ?? $this->_version;
        $this->clear();
    }

    /**
     * @throws Exception
     * @throws Exception\CurlError
     * @throws Exception\MapsError
     * @throws Exception\ServerError
     */
    public function load(array $options = []): static
    {
        $apiUrl = \sprintf(
            'https://geocode-maps.yandex.ru/%s/?%s', $this->_version,
            \http_build_query($this->_filters)
        );
        $curl = \curl_init($apiUrl);
        $options += [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPGET => 1,
            CURLOPT_FOLLOWLOCATION => 1,
        ];
        \curl_setopt_array($curl, $options);
        $data = \curl_exec($curl);
        $code = \curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (\curl_errno($curl)) {
            $error = \curl_error($curl);
            \curl_close($curl);
            throw new CurlError($error);
        }
        \curl_close($curl);
        if (\in_array($code, [500, 502])) {
            $msg = \strip_tags($data);
            throw new ServerError(\trim($msg), $code);
        }
        $data = \json_decode($data, true);
        if (empty($data)) {
            $msg = \sprintf('Can\'t load data by url: %s', $apiUrl);
            throw new Exception($msg);
        }
        if (!empty($data['error'])) {
            throw new MapsError($data['error'], $data['statusCode'] ?? 0);
        }

        $this->_response = new Response($data);

        return $this;
    }

    public function getResponse(): ?Response
    {
        return $this->_response;
    }

    /**
     * Очистка фильтров гео-кодирования
     */
    public function clear(): static
    {
        $this->_filters = ['format' => 'json'];
        // указываем явно значения по-умолчанию
        $this->setLang(self::LANG_RU)
            ->setOffset(0)
            ->setLimit(10);
        $this->_response = null;
        return $this;
    }

    /**
     * Гео-кодирование по координатам
     * @see http://api.yandex.ru/maps/doc/geocoder/desc/concepts/input_params.xml#geocode-format
     * @param float $longitude Долгота в градусах
     * @param float $latitude Широта в градусах
     */
    public function setPoint(float $longitude, float $latitude): static
    {
        $this->_filters['geocode'] = \sprintf('%F,%F', $longitude, $latitude);
        return $this;
    }

    /**
     * Географическая область поиска объекта
     * @param float $lengthLng Разница между максимальной и минимальной долготой в градусах
     * @param float $lengthLat Разница между максимальной и минимальной широтой в градусах
     * @param null|float $longitude Долгота в градусах
     * @param null|float $latitude Широта в градусах
     * @return self
     */
    public function setArea(float $lengthLng, float $lengthLat, float $longitude = null, float $latitude = null): static
    {
        $this->_filters['spn'] = \sprintf('%f,%f', $lengthLng, $lengthLat);
        if ($longitude && $latitude) {
            $this->_filters['ll'] = \sprintf('%f,%f', $longitude, $latitude);
        }
        return $this;
    }

    /**
     * Позволяет ограничить поиск объектов областью, заданной self::setArea()
     */
    public function useAreaLimit(bool $areaLimit): static
    {
        $this->_filters['rspn'] = $areaLimit ? 1 : 0;
        return $this;
    }

    /**
     * Гео-кодирование по запросу (адрес/координаты)
     */
    public function setQuery(string $query): static
    {
        $this->_filters['geocode'] = $query;
        return $this;
    }

    /**
     * Вид топонима (только для обратного геокодирования)
     */
    public function setKind(string $kind): static
    {
        $this->_filters['kind'] = $kind;
        return $this;
    }

    /**
     * Максимальное количество возвращаемых объектов (по-умолчанию 10)
     */
    public function setLimit(int $limit): static
    {
        $this->_filters['results'] = $limit;
        return $this;
    }

    /**
     * Количество объектов в ответе (начиная с первого), которое необходимо пропустить
     */
    public function setOffset(int $offset): static
    {
        $this->_filters['skip'] = $offset;
        return $this;
    }

    /**
     * Предпочитаемый язык описания объектов
     */
    public function setLang(string $lang): static
    {
        $this->_filters['lang'] = $lang;
        return $this;
    }

    /**
     * Ключ API Яндекс.Карт
     * @see https://tech.yandex.ru/maps/doc/geocoder/desc/concepts/input_params-docpage
     */
    public function setToken(string $token): static
    {
        $this->_filters['apikey'] = $token;
        return $this;
    }
}
