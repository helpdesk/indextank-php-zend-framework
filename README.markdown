IndexTank Zend Framework Client Library
=======================================

This library provides a client for the [IndexTank](http://www.indextank.com/) hosted real-time search API. The library defines methods to manage indexes (create and delete them), operate on them (add and delete documents, functions, etc), perform searches, etc.

IndexTank requires an API account for usage. You can get one by signing up for free at [www.indextank.com](http://www.indextank.com/).

Indexing and Searching
----------------------

### Example: Connect to IndexTank, create a new index and start indexing documents ###

We connect to IndexTank using our private URL. The private URL is displayed on your account dashboard when logged into IndexTank. Once connected, a new index is created on the server to store our list of books. We then add to books as documents to the index, ready to be searched. The books include three distinct fields, `text` (the standard field to be searched), `author` and `title` which will will later retrieve for listing.

    $client = new IndexTank_Client('PRIVATE_URL');
    
    $index = $client->createIndex('books');
    
    $index->addDocument('book1', array(
        'title'  => 'Little Red Riding Hood',
        'author' => 'Folktale',
        'text'   => 'A famous fairy tale about a young girl and a Big Bad Wolf'
    ));
    
    $index->addDocument('book2', array(
        'title'  => 'Hansel and Gretel',
        'author' => 'Brothers Grimm',
        'text'   => 'Hansel and Gretel are a young brother and sister threatened by a witch'
    ));

### Example: Search an existing index ###

In the second example, we reconnect to IndexTank and retrieve the previously created index. We then run a search query on the index, looping through the results printing them. Note that the search is instructed to retrieve all three fields from the index rather than just the default `text` field.

    $client = new IndexTank_Client('PRIVATE_URL');
    
    $index = $client->getIndex('books');
    
    $results = $index->search('fairy tale');
    
    foreach ($results as $result) {
        echo '<a href="/books/' . $result['docid'] . '">' . $result['snippets']['text'] . '</a><br>';
    }
    
    echo '<br>';

    $results = $index->search('big wolf OR witch', array('text', 'title', 'author'));
    
    foreach ($results as $result) {
        echo '<a href="/books/' . $result['docid'] . '">' . $result['fields']['title'] . '</a><br>';
        echo '<small><em>' . $result['fields']['author'] . '</em></small><br>';
        echo $result['snippets']['text'] . '<br>';
        echo '<br>';
    }

Removing Documents and Indexes
------------------------------
...

Configuration
-------------

Instead of passing the private IndexTank URL to the constructor every time, you can also configure the library globally as an application resource in your `application.ini`.

### Example: IndexTank configuration in `application.ini` ###

application.ini

    resources.indextank.private_url = 'PRIVATE_URL'

PHP Code

    $client = new IndexTank_Client();
    $index = $client->getIndex('books');
    $index->deleteDocument('book1');

Installation
------------

 * This library uses [Zend Framework](http://framework.zend.com/) which must be installed and available in the include path
 * [Download the latest version](https://github.com/helpdesk/indextank-php-zend-framework/zipball/master) of *indextank-php-zend-framework*
 * Extract and copy the directory `IndexTank/` into your include path

Contributing
------------

1. Fork it.
2. Create a branch (`git checkout -b my_changes`)
3. Commit your changes (`git commit -am "Added Beer Goggle API"`)
4. Push to the branch (`git push origin my_changes`)
5. Create an [Issue](https://github.com/helpdesk/indextank-php-zend-framework/issues) with a link to your branch
6. Enjoy a refreshing Diet Coke and wait

Copyright &copy; 2011 [Helpdesk](http://www.helpdeskhq.com/). See LICENSE for details.
