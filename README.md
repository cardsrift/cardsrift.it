# Welcome to Beaverlab webpack Setup
## Get started

1. Install [node.js](https://nodejs.org/)

2. If Windows

Install cross-env global

`npm i cross-env -g` (required one time)

3. Install npm packages

`npm i` or `npm ci` or `npm install`

4. Change name output folder

If necessary in `settings/environment.js` change the wpOutput's path with the name of your project. Remember to change `src/custom/style.css` to modify `Theme Name` etc.

5. Let's code!

* `npm run wp-dev` - Copies the files (static) to wp_theme directory and watch
* `npm run wp-build` - Copies the files (static) to wp_theme directory


## Template structure

```
public                       # Wordpress directory installation
settings                     # Webpack configs
src                          # Sources
├── custom                   # Directory *.php files
│   ├── includes             # Helpers functions wordpress
├── fonts                    # Fonts template
│   ├── icons                # Iconfont template
├── images                   # Images template
│   ├── icons                # Icons template
│   |   ├── other_icons      # Icons unused in sprite
│   |   ├── sprite_icons     # Icons used in sprite
├── js                       # Scripts template
│   ├── components           # Functions for components
│   ├── libs                 # Libriaries, plugins template
│   ├── utils                # Constants, helpers functions
├── scss                     # Styles template
│   ├── helpers              # Style extends, mixins and variables
│   ├── plugins              # Styles for plugins
.babelrc                     # Babel configuration
.env                         # Environment configuration
.eslintrc                    # Eslint rules
.gitignore                   # List of excluded files from Git
.sasslintrc                  # Sasslint rules
composer.json				 # Composer configuration
package.json                 # List of modules and other information
postcss.config.js            # Configuration of CSS post-processing
readme.md                    # Documentation template
webpack.config.js            # Configuration for launching webpack tasks
```

## Rules:

**File naming:**

| Type                          | Naming                | Exuals to           |
| ----------------------------  | :--------------------:| -------------------:|
| Sass files                    | _your_file.scss       |                     |
| JS component (js/components)  | nameOfYourFunction.js | *name of function*  |

