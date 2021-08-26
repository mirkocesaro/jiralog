# Jiralog
JiraLog allows you to track time on Jira with CLI



## Installation

Download all dependencies with

    composer install

 
Copy the `.env.example` file into `.env`.

Then, customize `.env` with your data.

- TEMPO_ENDPOINT: https://api.tempo.io/

   **Note:** This version supports only [Tempo.io](https://www.tempo.io/) in cloud
- TOKEN : create the new api integration in your tempo.io app on jira and copy the token
- AUTHOR_ACCOUNT_ID : you can find it in you jira profile



## Usage

     jiralog tempo:log <date> <from> <to> <issue> [<comment>]


example

     php jiralog tempo:log 2021-08-25 1330 1400 TASK-73 "Comment "


## Tests

    vendor/bin/phpunit


## Sources
This app is inspired by [Redlog](https://github.com/aleron75/redlog), a repo of [Aleron75](https://github.com/aleron75)

## License
Refer to [LICENSE](LICENSE) file for details.

## Changelog
TODO

## License
TODO