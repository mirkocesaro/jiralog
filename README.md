# Jiralog
JiraLog allows you to track time on Jira with CLI

## Installation

Download all dependencies with

    composer install

After composer has finished downloading all the dependencies, give execution permissions to `jiralog` file in root directory:

    chmod +x jiralog

Create a symlink to the jiralog script in a shared path, for example

     sudo ln -s /path/to/jiralog-project/jiralog /usr/local/bin/jiralog

## Configuration

Copy the `.env.example` file into `.env`.

Then, customize `.env` with your data.

| Command                | Description                                                                                                                                                                            |
|------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| TEMPO_ENDPOINT         | https://api.tempo.io/<br/>  **Note:** This version supports only [Tempo.io](https://www.tempo.io/) in cloud                                                                            |
| TOKEN                  | [create the new api integration in your tempo.io app](https://help.tempo.io/cloud/en/tempo-timesheets/developing-with-tempo/using-rest-api-integrations.html) on jira and copy the token |
| AUTHOR_ACCOUNT_ID      | you can copy it by the url of your jira profile<br/> <br/>![jira profile](docs/jira_profile.png)                                                                                       |
| JIRA_ENDPOINT          | https://bitbull.atlassian.net/                                                                                                                                                         |
| JIRA_EMAIL             | your jira e-mail address                                                                                                                                                               |
| JIRA_TOKEN             | [generate from atlassian account page](https://id.atlassian.com/manage-profile/security/api-tokens)                                                                                    |
| JIRA_BEARER_TOKEN      | leave empty for use basic authentication                                                                                                                                               | 


## Available commands

### Tempo

#### Work attribute 
Get the list of all work attriutes available on tempo

     jiralog tempo:work-attributes

#### Track worklog
Log a new worklog on tempo.io

     jiralog tempo:log <date> <from> <to> <issue> [<comment>] [--attributes='key:value']

example

    jiralog tempo:log 2021-08-25 1330 1400 TASK-73 "Comment"

another example with an attribute

    jiralog tempo:log 2021-08-25 1330 1400 TASK-73 "Comment" --attributes='_Activity_:Analysis'

same example with alias

    jiralog tempo:log 2021-08-25 1330 1400 TASK-73 "Comment" -a _Activity_:Analysis

#### Extract worklogs
Extract worklogs for current user

     jiralog tempo:extract-worklogs <start_date> <end_date>

without arguments will be extracted current day worklogs



### Jira

#### Search for Issue
Search for issue with query:

     jiralog jira:issue-picker <query>

Search for issue with JQL:

     jiralog jira:issue-picker <jql> --jql

Search on custom configured jira: (ex ADEO)

     jiralog jira:issue-picker <jql> --jql --prefix <prefix>



#### Get Worklogs for Issue
Get worklogs for issue key or ID:

     jiralog jira:worklogs <issues>...

note: you can specify multiple issues

## Tests

    vendor/bin/phpunit


## Sources

[jiralog](https://github.com/mirkocesaro/jiralog) by @mirkocesareo

This app is inspired by [Redlog](https://github.com/aleron75/redlog), a repo of [Aleron75](https://github.com/aleron75)

## License
Refer to [LICENSE](LICENSE) file for details.