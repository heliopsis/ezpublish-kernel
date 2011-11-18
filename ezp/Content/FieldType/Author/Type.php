<?php
/**
 * File containing the Author class
 *
 * @copyright Copyright (C) 1999-2011 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */

namespace ezp\Content\FieldType\Author;
use ezp\Content\FieldType,
    ezp\Content\FieldType\Value as BaseValue,
    ezp\Base\Exception\BadFieldTypeInput,
    ezp\Base\Exception\InvalidArgumentType;

/**
 * Author field type.
 *
 * Field type representing a list of authors, consisting of author name, and
 * author email.
 */
class Type extends FieldType
{
    const FIELD_TYPE_IDENTIFIER = "ezauthor";
    const IS_SEARCHABLE = true;

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \ezp\Content\FieldType\Author\Value
     */
    protected function getDefaultValue()
    {
        return new Value;
    }

    /**
     * @see \ezp\Content\FieldType::canParseValue()
     */
    protected function canParseValue( BaseValue $inputValue )
    {
        if ( $inputValue instanceof Value )
        {
            if ( !$inputValue->authors instanceof AuthorCollection )
            {
                throw new BadFieldTypeInput( $inputValue, get_class( $this ) );
            }

            return $inputValue;
        }

        throw new InvalidArgumentType( 'value', 'ezp\\Content\\FieldType\\Author\\Value' );
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @return array
     */
    protected function getSortInfo()
    {
        return false;
    }
}
