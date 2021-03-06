<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.0.1
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ElasticSearch\TestSuite;

use Cake\ElasticSearch\Datasource\Connection;
use Elastica\Document as ElasticaDocument;
use Elastica\Query\MatchAll;
use Elastica\Type\Mapping as ElasticaMapping;

/**
 * A Test fixture implementation for elastic search.
 *
 * Lets you seed indexes for testing your application.
 *
 * Class extension is temporary as fixtures are missing an interface.
 */
class TestFixture
{
    /**
     * The connection name to use for this fixture.
     *
     * @var string
     */
    public $connection = 'test';

    /**
     * The Elastic search type definition for this type.
     *
     * The schema defined here should be compatible with ElasticSearch's
     * mapping API and Elastica
     *
     * @var array
     * @see http://elastica.io/getting-started/storing-and-indexing-documents.html#define-mapping
     */
    public $schema = [];

    /**
     * The records to insert.
     *
     * @var array
     */
    public $records = [];

    /**
     * A list of connections this fixtures has been added to.
     *
     * @var array
     */
    public $created = [];

    /**
     * Create the mapping for the type.
     *
     * @param \Cake\ElasticSearch\Datasource\Connection $db The Elasticsearch
     *  connection
     * @return void
     */
    public function create(Connection $db)
    {
        if (empty($this->schema)) {
            return;
        }
        $index = $db->getIndex();
        if (!$index->exists()) {
            $index->create();
        }

        $type = $index->getType($this->table);
        $mapping = new ElasticaMapping();
        $mapping->setType($type);
        $mapping->setProperties($this->schema);
        $mapping->send();
    }

    /**
     * Insert fixture documents.
     *
     * @param \Cake\ElasticSearch\Datasource\Connection $db The Elasticsearch
     *  connection
     * @return void
     */
    public function insert(Connection $db)
    {
        if (empty($this->records)) {
            return;
        }
        $documents = [];
        $index = $db->getIndex();
        $type = $index->getType($this->table);

        foreach ($this->records as $data) {
            $id = '';
            if (isset($data['id'])) {
                $id = $data['id'];
            }
            unset($data['id']);
            $documents[] = $type->createDocument($id, $data);
        }
        $type->addDocuments($documents);
        $index->refresh();
        $this->created[] = $db->configName();
    }

    /**
     * Drops a mapping and all its related data.
     *
     * @param \Cake\ElasticSearch\Datasource\Connection $db The Elasticsearch
     *  connection
     * @return void
     */
    public function drop(Connection $db)
    {
        $index = $db->getIndex();
        $type = $index->getType($this->table);
        $type->delete();
        $index->refresh();
    }

    /**
     * Truncate the fixture type.
     *
     * @param \Cake\ElasticSearch\Datasource\Connection $db The Elasticsearch
     *  connection
     * @return void
     */
    public function truncate(Connection $db)
    {
        $query = new MatchAll();
        $index = $db->getIndex();
        $type = $index->getType($this->table);
        $type->deleteByQuery($query);
        $index->refresh();
    }
}
