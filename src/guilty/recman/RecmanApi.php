<?php

namespace Guilty\Recman;

use GuzzleHttp\Client;

/**
 * Service for querying the Recman API,
 * Recman has an API limit of 200 requests per day, if you don't plan on
 * handling caching yourself, use the CachedRecmanService class instead.
 *
 * Class RecmanService
 * @package Guilty\Recman
 * @see https://help.recman.no/no/help/api/
 */
class RecmanApi
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string The Recman API key,
     */
    protected $apiKey;

    /**
     * @var string The base url for the Recman API
     */
    protected $baseUrl = "https://api.recman.no/v1.php";

    public function __construct($apiKey, Client $client)
    {
        $this->apiKey = $apiKey;
        $this->client = $client;
    }

    /**
     * Performs an authenticated request with the provided query parameters.
     *
     * @param array $params the query parameters to send to the API
     * @return array|null
     * @throws \Exception
     */
    protected function performRequest($params = [])
    {
        $response = $this->client->request("GET", $this->buildUrl($params));
        $data = json_decode($response->getBody()->getContents(), true);

        $this->throwIfError($data);

        return $data;
    }

    /**
     * Throws an exception with the API error message and code if we got an error.
     * @param array $data Response from API
     * @throws \Exception
     * @returns void
     */
    protected function throwIfError($data)
    {
        if (isset($data["error"])) {

            if (isset($data[0])) {
                throw new \Exception(
                    $data["error"][0]["message"],
                    $data["error"][0]["code"]
                );
            }

            throw new \Exception(
                $data["error"]["message"],
                $data["error"]["code"]
            );
        }
    }

    /**
     * Builds the API url from the query parameters
     *
     * @param array $params
     * @return string the complete API URL
     */
    protected function buildUrl($params)
    {
        $query = array_merge($params, [
            "key" => $this->apiKey,
            "type" => "json",
            "fields" => $this->prepareFieldParams($params)
        ]);

        return $this->baseUrl . "?" . http_build_query($query);
    }

    /**
     * Converts the "fields" array to a comma separated list.
     *
     * @param array $fields the fields to convert
     * @return string Comma-separated list of fields
     */
    protected function prepareFieldParams($params)
    {
        if (isset($params["fields"])) {
            if (is_array($params["fields"])) {
                return implode(",", $params["fields"]);
            } else {
                return $params["fields"];
            }
        }

        return null;
    }


    public function getBranchList()
    {
        return $this->performRequest([
            "scope" => "branch_list"
        ]);
    }

    public function getBranchCategoryList()
    {
        return $this->performRequest([
            "scope" => "branch_category_list"
        ]);
    }

    public function getSectorList()
    {
        return $this->performRequest([
            "scope" => "sector_list"
        ]);
    }

    public function getExtentList()
    {
        return $this->performRequest([
            "scope" => "extent_list"
        ]);
    }

    public function getLocationList($field)
    {
        $validFields = ["city", "region", "country", "world-country-list", "nationality-list"];

        if (!in_array($field, $validFields)) {
            throw new \InvalidArgumentException(
                "Field: $field is not valid, only one of the following can be used: " . implode(", ", $validFields)
            );
        }

        return $this->performRequest([
            "scope" => "location",
            "fields" => $field
        ]);
    }

    public function getJobPostList()
    {
        return $this->performRequest([
            "scope" => "job_post",
            "fields" => [
                "name", "ingress", "body", "logo", "from_date", "to_date", "title", "place",
                "deadline", "facebook", "twitter", "webpage", "num_positions", "video",
                "external_ats", "created", "updated", "position_start", "salary",
                "company_name", "address1", "address2", "city", "postal_code",
                "country", "keywords", "contact_persons", "country_id", "region_id", "city_id",
                "first_branch", "first_branch_category_id", "first_branch_id",
                "second_branch_category_id", "second_branch_id", "sector_id", "extent_id"
            ]
        ]);
    }

    public function getDepartmentList()
    {
        return $this->performRequest([
            "scope" => "department",
            "fields" => [
                "name", "address1", "address2",
                "postal_code", "city", "country",
                "phone", "email", "fax", "logo",
                "number", "corporation_id"
            ]
        ]);
    }

    public function getCorporation()
    {
        return $this->performRequest([
            "scope" => "corporation",
            "fields" => [
                "name", "phone", "email", "logo", "footer_logo", "about",
                "webpage", "facebook", "linkedin", "twitter", "rm_page"
            ]
        ]);
    }

    /**
     * The candidate list is paginated at 5000 entries, if you require more than this, fetch page 2
     *
     * @param int $page
     * @return array|null
     */
    public function getCandidateList($page = 1)
    {
        return $this->performRequest([
            "scope" => "candidate_list",
            "page" => $page,
            "fields" => [
                "candidateID", "firstName", "lastName", "email", "profilePicture",
                "mobilePhone", "officePhone", "homePhone", "facebook", "linkedin",
                "twitter", "address1", "address2", "postalCode", "city", "country"
            ]
        ]);
    }

    public function getCandidate($candidateId)
    {
        return $this->performRequest([
            "scope" => "candidate_list",
            "c_candidate_id" => $candidateId,
            "fields" => [
                "candidateID", "firstName", "lastName", "email", "profilePicture",
                "mobilePhone", "officePhone", "homePhone", "facebook", "linkedin",
                "twitter", "address1", "address2", "postalCode", "city", "country"
            ]
        ]);
    }

    public function getCandidateAttributeList()
    {
        return $this->performRequest([
            "scope" => "candidate_attribute_list"
        ]);
    }

    public function getCandidateAttributes()
    {
        return $this->performRequest([
            "scope" => "candidate_attribute"
        ]);
    }

    public function getCandidateLanguageList()
    {
        return $this->performRequest([
            "scope" => "language_list",
            "fields" => [
                "name"
            ]
        ]);
    }

    public function getUserList($departmentIds = [], $corporationIds = [], $tagIds = [])
    {
        return $this->performRequest([
            "scope" => "user",
            "c_department_id" => implode(",", $departmentIds),
            "c_corporation_id" => implode(",", $corporationIds),
            "c_tag_id" => implode(",", $tagIds),
            "fields" => [
                "first_name", "last_name", "title", "mobile_phone",
                "office_phone", "email", "image", "facebook",
                "linkedin", "twitter", "corporation_id", "department_id"
            ]
        ]);
    }

    public function getUserTagList()
    {
        return $this->performRequest([
            "scope" => "user_tag_list",
            "fields" => ["name"]
        ]);
    }

}

