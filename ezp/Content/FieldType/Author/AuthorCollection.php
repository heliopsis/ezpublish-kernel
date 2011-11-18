<?php
/**
 * File containing the AuthorCollection class
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace ezp\Content\FieldType\Author;
use ezp\Base\Collection\Type as TypeCollection;

/**
 * Author collection.
 * This collection can only hold {@link \ezp\Content\FieldType\Author\Author} objects
 */
class AuthorCollection extends TypeCollection
{
    /**
     * @var \ezp\Content\FieldType\Author\Value
     */
    protected $authorValue;

    public function __construct( Value $authorValue, array $elements = array() )
    {
        $this->authorValue = $authorValue;
        // Call parent constructor without $elements because all author elements
        // must be given an id by $this->offsetSet()
        parent::__construct( 'ezp\\Content\\FieldType\\Author\\Author' );
        foreach ( $elements as $i => $author )
        {
            $this->offsetSet( $i, $author );
        }
    }

    /**
     * Adds a new author to the collection
     *
     * @param int $offset
     * @param \ezp\Content\FieldType\Author\Author $value
     */
    public function offsetSet( $offset, $value )
    {
        $aAuthors = $this->getArrayCopy();
        parent::offsetSet( $offset, $value );
        if ( !isset( $value->id ) || $value->id == -1 )
        {
            if ( !empty( $aAuthors ) )
            {
                $value->id = end( $aAuthors )->id + 1;
            }
            else
            {
                $value->id = 1;
            }
        }
    }

    /**
     * Removes authors from current collection with a list of Ids
     *
     * @param array $authorIds Author's Ids to remove from current collection
     */
    public function removeAuthorsById( array $authorIds )
    {
        $aAuthors = $this->getArrayCopy();
        foreach ( $aAuthors as $i => $author )
        {
            if ( in_array( $author->id, $authorIds ) )
            {
                unset( $aAuthors[$i] );
            }
        }

        $this->exchangeArray( $aAuthors );
    }
}
