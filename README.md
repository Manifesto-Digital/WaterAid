# wateraid

Todo: Update README with installation instructions

* DDEV
* Storybook
* Single Directory Components (https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components)


To install site: `make install`

## Backend


## Frontend

### Useful commands
* `make install-frontend` (runs npm install in theme folder)
* `make build-frontend` (compiles the front end)
* `make watch-frontend` (watch for changes in the front end)
* `make install-storybook` (install SB)
* `make storybook` (runs SB)
* `make all-stories` (generates all SB stories)
* `make watch-all-stories` (watch for SB story changes)
* `make generate-sdc` (wizard to create single directory component files)

### Single Directory Components

To create new components:

1) Create new component (can use `make generate-sdc` to make it quicker)
2) Rename the empty .css file to .sass
3) Update the *.component.yml file and link to the compiled component css
```
  libraryOverrides:
    css:
      component:
        ../../dist/css/components/component_name/component_name.css: {}
```
4) Create component_name.stories.twig
5) Run `make all-stories` to generate storybook files (you can also watch for changes)
5) Add entry point for scss and js to `docroot/themes/custom/wateraid/webpack.config.js`
6) Create paragraph in CMS and paragraph twig template in code base



### Storybook

To install SB and dependancies locally: `make install-storybook`

If you get an error installing storybook, then edit the package.json file and add `--no-open` to the Storybook script:
`"storybook": "storybook dev -p 6006 --no-open",`

You should now be able to run `make storybook` to start up Storybook

The default URLs for Storybook hangs on Macs, so access Storybook on your local via:
https:/wateraid.ddev.site:6006

### Acquia
PRs merging into the main, develop or staging branches will automatically deploy to the Acquia holding environments for
testing purposes.
