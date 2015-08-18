# encoding: utf-8
Feature: In order to support Behat, an easy to use adapter should be provided.

  Scenario Outline: ResultsConversion
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    When the Adapter reports <from_result> with exception <exception> it should be converted to <to_result>

    Examples: 
      | from_result | exception                                      | to_result |
      | "PENDING"   | -                                              | "SKIPPED" |
      | "UNDEFINED" | -                                              | "SKIPPED" |
      | "SKIPPED"   | -                                              | "SKIPPED" |
      | "PASSED"    | -                                              | "SUCCESS" |
      | "FAILED"    | "PHPUnit_Framework_AssertionFailedError"       | "FAILURE" |
      | "FAILED"    | "Behat\Mink\Exception\ExpectationException"    | "FAILURE" |
      | "FAILED"    | "Behat\Mink\Exception\ElementHtmlException"    | "FAILURE" |
      | "FAILED"    | "\RuntimeException"                            | "ERROR"   |

  Scenario: ConvertPyStrings
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    When I convert a pystringObject with:
      """
      This is a pystring |558%$*|
      """
    Then I should get a string "This is a pystring |558%$*|" with pystring delimiters and a single intent

  Scenario: Collect backgroundsteps
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    And I have a feature file:
      """
      Feature: Testing a feature
      
        Background: test
        Given I have a background step
        And I have also a second background step
      
        Scenario: Test
        Given I have a TestScenario
      """
    When I run Behat
    Then the Adapter should report the feature "Testing a feature"
    And the Adapter should report the scenario "Test" with following steps:
      """
      Given I have a background step
      And I have also a second background step
      Given I have a TestScenario
      """
    And the Adapter should send "SKIPPED" 3 times to the server

  Scenario: Feature-File with Scenario and ScenarioOutline
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    And I have a feature file:
      """
      Feature: Test another Feature
      
      Scenario Outline: AnExampleScenario
      Given I have a step with <value1> and <value2>
      Examples:
      |value1     | value2   |
      |"Test1-1"  | "Test1-2"|
      |"Test2-1"  | "Test2-2"|
      
      Scenario: AnotherScenario
      Given I have a scenario with a step
      And there is also a second step
      """
    When I run Behat
    Then the Adapter should report the feature "Test another Feature"
    And the Adapter should report the scenario "AnExampleScenario" with following steps:
      """
      Given I have a step with "Test1-1" and "Test1-2"
      Given I have a step with "Test2-1" and "Test2-2"
      """

    And the Adapter should report the scenario "AnotherScenario" with following steps:
      """
      Given I have a scenario with a step
      And there is also a second step
      """

  Scenario: Outlines with backgroundsteps
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    And I have a feature file:
      """
      Feature: OutlineFeature
      Background: test
      Given I have a backgroundstep
      And a second backgroundstep
      
      Scenario Outline: Test
      Given I have a step with <value1> and <value2>
      Examples:
      |value1    | value2   |
      |"Test1-1" | "Test1-2"|
      |"Test2-1" | "Test2-2"|
      """
    When I run Behat
    Then the Adapter should report the feature "OutlineFeature"
    And the Adapter should report the scenario "Test" with following steps:
      """
      Given I have a backgroundstep
      And a second backgroundstep
      Given I have a step with "Test1-1" and "Test1-2"
      Given I have a backgroundstep
      And a second backgroundstep
      Given I have a step with "Test2-1" and "Test2-2"
      """

  Scenario: Datatables
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    And I have a feature file:
      """
      Feature: DataTableFeature
      Scenario: Test with datatable
      Given I have a step with a datatable:
      |Col1         |Col2       |
      |"String1"    |32.4       |
      |"String2"    |12.2       |
      And I have a second step with a datatable:
      |Col3         |Col4       |
      |1            |"String"   |
      """
    When I run Behat
    Then the Adapter should send the steptext: "Given I have a step with a datatable:" with the datatable:
      |Col1         |Col2       |
      |"String1"    |32.4       |
      |"String2"    |12.2       |
    And the Adapter should send the steptext: "And I have a second step with a datatable:" with the datatable:
      |Col3         |Col4       |
      |1            |"String"   |

  Scenario: DocStrings
    Given I have an instance of the BDD-Adapter for Behat without a server connection
    And I have a feature file with a step "Given I have a step" and a docstring "hello docstring"
    When I run Behat
    Then the Adapter should report the step as follows:
	"""
	Given I have a step
	 <DOCSTRING>
	 hello docstring
	 <DOCSTRING>
	"""