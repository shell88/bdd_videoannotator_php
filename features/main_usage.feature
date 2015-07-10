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