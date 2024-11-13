## Project title

bookindex-9.x

## Description

bookindex-9.x is a Drupal 9 module that adds index functionality to Drupal books. Although search functions can be useful to find certain items an index will lead to the correct description instantly.

The project should be considered an alpha version. The project is currently not in use but worked in a Drupal 9.x and 11.x test environment. The code is not up to standard. No help page or options are available. Internationalisation and localisation is not available. The module is not in the Drupal module repository.

The module is under active development.

## Installation

Download [Simple HTML DOM](https://simplehtmldom.sourceforge.io/) and copy simple_html_dom.php into /libraries/simle_html_dom/

Copy all the files into /modules/bookindex

Copy book/templates/node-export-html.html.twig to your Theme directory and replace `<article>` by `<article id = "node-{{ node.id }}">`.

Enable the module.

## Use

Add terms into your book pages by adding an anchor in the book page with the name index\[Keyword].

The index can be added to a book page as a block, for example in the sidebar or on a separate book page. The block will contain all items of the book. To add it as a separate book page follow these steps:

1. Add a page to the end of the book titled Index, but leave the content blank.
2. Add the block "Book index block" to the content section below Title.
3. Restrict the block to the index page.

The index is now in the books table of content and the navigation links for the book are shown below the index.

It is also possible to show the index on its own page with the url /book/index/\[node]. The page will show all the items in the page and its subpages. The page is not in the book table of contents and the navigational links are not shown.

## Styling

To change the style of the index the following classes are available:

- `.bookindex` - a wrapper for the whole index.
- `.bookindex--navigation` - the navigation bar.
- `.bookindex--index` - a wrapper for the index itself.
- `.bookindex--index-item` - an item within the index: the initial and associated terms.

## Credits

Written by Christiaan ter Veen <https://www.rork.nl/>

Depends on:

- Drupal <https://www.drupal.org/>
- Drupal book <https://www.drupal.org/project/book>
- Simple HTML DOM <https://simplehtmldom.sourceforge.io/>

## License

bookindex-9.x is licensed under the GNU General Public License, version 2 or later. That means you are free to download, reuse, modify, and distribute any files in this repository under the terms of either the GPL version 2 or version 3, and to run this module in combination with any code with any license that is compatible with either versions 2 or 3.
http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
