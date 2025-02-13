<?php

/**
 * IPP - PHP Project Core
 * @author Radim Kocman
 * @author Zbyněk Křivka
 * ---
 * DO NOT MODIFY THIS FILE!
 */

namespace IPP\Core;

/**
 * Common script return codes from the project specification
 */
abstract class ReturnCode
{
    public const int OK = 0;
    public const int PARAMETER_ERROR = 10;
    public const int INPUT_FILE_ERROR = 11;
    public const int OUTPUT_FILE_ERROR = 12;
    public const int INVALID_XML_ERROR = 31;
    public const int INVALID_SOURCE_STRUCTURE = 32;
    public const int SEMANTIC_ERROR = 52;
    public const int OPERAND_TYPE_ERROR = 53;
    public const int VARIABLE_ACCESS_ERROR = 54;
    public const int FRAME_ACCESS_ERROR = 55;
    public const int VALUE_ERROR = 56;
    public const int OPERAND_VALUE_ERROR = 57;
    public const int STRING_OPERATION_ERROR = 58;
    public const int INTEGRATION_ERROR = 88;
    public const int INTERNAL_ERROR = 99;
}
