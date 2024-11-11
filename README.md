## Project title

bookindex-9.x

## Description

bookindex-9.x is a Drupal 9 module that adds index functionality to Drupal books. Although search functions can be useful to find certain items an index will lead to the correct description instantly. The index is also exported to a printable version by bookexportrtf-9.x.

The project should be considered a alpha version. The project is currently not in use but worked in a Drupal 9.x and 11.x test environment. The code is not up to standard. No help page or options are available. Internationalisation and localisation is not available. The module is not in the Drupal module repository.

The module is under active development.

## Installation

Download [Simple HTML DOM](https://simplehtmldom.sourceforge.io/) and copy simple_html_dom.php into /libraries/simle_html_dom/

Copy all the files into /modules/bookindex

Copy book/templates/node-export-html.html.twig to your Theme directory and replace `<article>` by `<article id = "node-{{ node.id }}">`.

Enable the module.

## Use

Add terms into your book pages by adding an anchor in the book page with the name index\[Keyword].

The index is shown for the book page and subpages on the url /book/index/\[node].

## Credits

Written by Christiaan ter Veen <https://www.rork.nl/>

Depends on:

- Drupal <https://www.drupal.org/>
- Drupal book <https://www.drupal.org/project/book>
- Simple HTML DOM <https://simplehtmldom.sourceforge.io/>

## License

bookindex-9.x is licensed under the GNU General Public License, version 2 or later. That means you are free to download, reuse, modify, and distribute any files in this repository under the terms of either the GPL version 2 or version 3, and to run this module in combination with any code with any license that is compatible with either versions 2 or 3.
http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
