<?php
	/**
	 *
	 * Propositional Logic Parser
	 * @author Jason Gillman Jr. <jason@rrfaae.com>
	 * 
	 */
	class propLogic
	{
		// General Properties
		private $bitCount, $propositionCount, $symbols, $propositions;
		private	$symbolInt, $validElements;


		/**
		 *
		 * @param array $symbols An array of the symbols being used
		 * @param array $proposition An array of the propositions
		 *
		 */
		public function __construct($symbols, $propositions)
		{
			$this->bitCount	= count($symbols);
			$this->propositionCount	=	count($propositions);
			$this->symbolInt = (pow(2,$this->bitCount) - 1);

			$this->symbols = $symbols;
			$this->propositions = $propositions;

			// Logical Operators
			$this->logicOperators[] = '~'; // Negation
			$this->logicOperators[] = '>'; // Material Implication
			$this->logicOperators[] = '^'; // Conjunction
			$this->logicOperators[] = '|'; // Inclusive Or
			$this->logicOperators[]	= '('; // Begin Grouping
			$this->logicOperators[] = ')'; // End Grouping

			// Accepted input - comprised of the logic operators as well as the symbols
			foreach($this->logicOperators as $operator)
			{
				$this->validElements[] = $operator;
			}

			foreach($this->symbols as $symbol)
			{
				$this->validElements[] = $symbol;
			}
			// End Accepted Input

			$this->sanityCheck(); // Verify we don't have characters that fall outside of the logicalElements array
		}

		private function sanityCheck() // Does a character check for every proposition
		{
			foreach($this->propositions as $proposition)
			{
				$tempArray = str_split($proposition);
				foreach($tempArray as $char)
				{
					if($char != " ") // Ignore Spaces
					{
						if(!in_array($char, $this->validElements))
						{
							exit('Illegal Character');
						}
					}
				}
			}
		}

		public function generateTable()
		{
			/**
			 * Order of battle
			 * 1. Look at integer and use to determine binary (truth) values for symbols
			 * 2. For each proposition, break down by character to determine ultimate truth value
			 * 3. ?
			 * 4. Profit!
			 */
		}

		public function symbolRunner()
		{
			while($this->symbolInt >= 0)
			{
				printf("%0".$this->bitCount."b\n", $this->symbolInt);
				--$this->symbolInt;
			}
		}
	}
?>