<?php
declare(strict_types=1);

require __DIR__ . '/vendor/mck89/rebuilder/lib/REBuilder/REBuilder.php';

REBuilder\REBuilder::registerAutoloader();

echo "This script is not perfect, it may generate regexes that aren't actually working.\n";

$Rulesets = parse_ini_file( __DIR__ . '/../rules.ini', true, INI_SCANNER_RAW );

foreach( $Rulesets as $Type => $Rules )
{
	foreach( $Rules as $Name => $RuleRegexes )
	{
		if( !is_array( $RuleRegexes ) )
		{
			$RuleRegexes = [ $RuleRegexes ];
		}

		$File = __DIR__ . '/types/' . $Type . '.' . $Name . '.txt';
		$Tests = [];

		if( file_exists( $File ) )
		{
			$Tests = file( $File, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		}

		$Output = [];
		$Added = false;

		// Skip generating certain regexes
		if( $Name !== 'MUS_OGG' && $Name !== 'Bitsquid' && $Name !== 'Python' && $Name !== 'cURL')
		{
			foreach( $RuleRegexes as $Regex )
			{
				//exec( 'node ' . escapeshellarg( __DIR__ . '/randexp/index.js' ) . ' ' . escapeshellarg( $Regex ), $Output );

				$ParsedRegex = \REBuilder\REBuilder::parse( '~' . $Regex . '~' );

				var_dump( GenerateRegexString( $ParsedRegex ) );
			}
		}

		foreach( $Output as $Line )
		{
			if( !in_array( $Line, $Tests, true ) )
			{
				$Added = true;
				$Tests[] = $Line;
			}
		}

		if( !$Added )
		{
			continue;
		}

		sort( $Tests );
		file_put_contents( $File, implode( "\n", $Tests ) . "\n" );

		echo "Updated {$Type}.{$Name}\n";
	}
}

echo "Now running tests...\n";
require __DIR__ . '/Test.php';

function GenerateRegexString( REBuilder\Pattern\AbstractContainer $ParsedRegex ) : string
{
	$Str = '';

	foreach( $ParsedRegex->getChildren() as $Child )
	{
		if( $Child instanceof REBuilder\Pattern\Char )
		{
			if( $Child->getRepetition() )
			{
				//throw new Exception( 'Unhandled regex feature: ' . $Child->render() );
			}

			$Str .= $Child->getChar();
		}
		else if( $Child instanceof REBuilder\Pattern\CharClass )
		{
			if( $Child->getNegate() )
			{
				throw new Exception( 'Unhandled regex feature: ' . $Child->render() );
			}

			if( $Child->getRepetition() )
			{
				//throw new Exception( 'Unhandled regex feature: ' . $Child->render() );
			}

			foreach( $Child->getChildren() as $Child )
			{
				$Str .= $Child->render();
			}
		}
		else if( $Child instanceof REBuilder\Pattern\Dot )
		{
			if( $Child->getRepetition() )
			{
				//throw new Exception( 'Unhandled regex feature: ' . $Child->render() );
			}

			$Str .= '@';
		}
		else if( $Child instanceof REBuilder\Pattern\SubPattern )
		{
			if( $Child->getCapture() )
			{
				throw new Exception( 'Regex must not be capturing: ' . $Child->render() );
			}

			$Str .= GenerateRegexString( $Child );
		}
		else if( $Child instanceof REBuilder\Pattern\AlternationGroup )
		{
			foreach( $Child->getChildren() as $SubChild )
			{
				$Str .= GenerateRegexString( $SubChild );
			}
		}
		else
		{
			throw new Exception( 'Unhandled regex feature: ' . $Child::class );
		}
	}

	return $Str;
}
