# encoding: utf-8
Feature: In order to use the annotation service a simple configurable Java API should provide handy methods
  to use these features.

  Scenario: Server Connection
    Given I start the server from the provided client package
    Then i must be able to connect to the server functions without an error
    And i must be able to stop the server

  Scenario: Annotations
    Given I start the server from the provided client package
    When i start a new scenario
    And I add a samplestep to the scenario
    And I stop the scenario
    Then i should get an annotation file
    And i should get an video file

  Scenario Outline: ResultsConversion
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    When the Adapter reports <from_result> with exception <exception> it should be converted to <to_result>

    Examples: 
      | from_result | exception                                      | to_result |
      | "PENDING"   |                                                | "SKIPPED" |
      | "UNDEFINED" |                                                | "SKIPPED" |
      | "SKIPPED"   |                                                | "SKIPPED" |
      | "PASSED"    |                                                | "SUCCESS" |
      | "FAILED"    | "PHPUnit_Framework_AssertionFailedError"       | "FAILURE" |
      | "FAILED"    | "Behat\Mink\Exception\ExpectationException"    | "FAILURE" |
      | "FAILED"    | "Behat\Mink\Exception\ElementHtmlException"    | "FAILURE" |
      | "FAILED"    | "\RuntimeException"                            | "ERROR"   | 
