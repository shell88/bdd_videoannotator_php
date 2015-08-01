# encoding: utf-8
Feature: In order to use the annotation service a simple configurable PHP API should provide handy methods
  to use these features.
  
  Scenario: Server Connection
    Given I start the server from the provided client package
    Then i must be able to connect to the server functions without an error
    And i must be able to stop the server