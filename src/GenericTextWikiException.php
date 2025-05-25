<?php

namespace Horde\Text\Wiki;

use DomainException;

/**
 * A generic DomainException with TextWikiException interface
 *
 * Do NOT derive classes from this class or Horde\Exception\Exception but rather derive from PHP Builtin exceptions and add the TextWikiException interface.
 * Put shared functionality into a trait instead.
 */
final class GenericTextWikiException extends DomainException implements TextWikiException
{
}
