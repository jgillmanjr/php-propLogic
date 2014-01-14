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
		private $bitCount, $symbols, $propositions;
		private	$symbolInt, $validCharacters, $opFunctions, $symbolTruthMap, $validFirstChars;
		private $currentArray, $stackLow;

		/**
		 *
		 * @param array $symbols An array of the symbols being used
		 * @param array $proposition An array of the propositions
		 *
		 */
		public function __construct($symbols, $propositions)
		{
			// Common variable setup
			$this->bitCount	= count($symbols); // Determine the bit length based on the number of symbols
			$this->symbolInt = (pow(2,$this->bitCount) - 1); // Set the symbol integer to a value where all bits are true (truth tables usually start with all values being true)

			$this->symbols = $symbols; // Copy to the private property
			$this->propositions = $propositions; // Copy to the private property

			$this->sanityCheck(); // Verify that the propositions are legit
			$this->truthMapper(); // Create the array mapping the symbol boolean values for all the integers possible

			// Operator setup
			// Set for a given input operator as the index, the array is operators with lower precedence in the stack
			$this->stackLow['>'] = ['('];
			$this->stackLow['|'] = ['(', '>'];
			$this->stackLow['^'] = ['(', '>', '|'];
			$this->stackLow['~'] = ['(', '>', '|', '^']; // Unary negation

			// $this->opFunctions will contain the anonymous functions to make things work(TM)
			$this->opFunctions['>'] =
				function ($firstBool, $secondBool)
				{
					if(($firstBool === TRUE) AND ($secondBool === FALSE))
					{
						$result = FALSE;
					}
					else
					{
						$result = TRUE;
					}
					return $result;
				};
			$this->opFunctions['|'] =
				function ($firstBool, $secondBool)
				{
					$result = ($firstBool OR $secondBool);
					return $result;
				};
			$this->opFunctions['^'] =
				function ($firstBool, $secondBool)
				{
					$result = ($firstBool AND $secondBool);
					return $result;
				};
			$this->opFunctions['~'] =
				function ($onlyBool)
				{
					$result = !$onlyBool;
					return $result;
				};
				// End operator setup
		}

		private function sanityCheck() // Does a character check for every proposition
		{
			// The following is used to determine valid characters and valid first characters
			$this->validCharacters[]	=	'~'; // Negation
			$this->validFirstChars[]	=	'~';
			$this->validCharacters[]	=	'>'; // Material Implication
			$this->validCharacters[]	=	'^'; // Conjunction
			$this->validCharacters[]	=	'|'; // Inclusive Or
			$this->validCharacters[]	=	'('; // Begin Grouping
			$this->validFirstChars[]	=	'(';
			$this->validCharacters[]	=	')'; // End Grouping

			foreach($this->symbols as $symbol)
			{
				if(in_array($symbol, $this->validCharacters)) // Throw an error and exit
				{
					exit('Passed in symbol is already an operator' . "\n");
				}
				$this->validCharacters[]	=	$symbol;
				$this->validFirstChars[]	=	$symbol;
			}
			// End valid characters

			// Check each proposition for validity
			foreach($this->propositions as $proposition)
			{
				$proposition = preg_replace('/\s+/', '', $proposition); // Strip out whitespace
				$checkChars	=	str_split($proposition); // Generate the character array

				// Make sure the propositions have a valid first character
				in_array($checkChars[0], $this->validFirstChars)	?:	exit('The proposition ' . $proposition . ' has an invalid first character: ' . $checkChars[0] . "\n");

				// Make sure all the characters are valid, and while we're at it, do the parenthesis check
				$parenCount = 0; // Init, even if there aren't parens in the prop
				foreach($checkChars as $char)
				{
					in_array($char, $this->validCharacters)	?:	exit('The proposition ' . $proposition . ' has an invalid character: ' . $char . "\n");

					$char == '('	?	$parenCount++	:	FALSE;
					$char == ')'	?	$parenCount--	:	FALSE;
					$parenCount >= 0	?:	exit('Front loaded left parenthesis in proposition: ' . $proposition . "\n");
				}
				$parenCount == 0	?:	exit('Parenthesis mismatch in proposition: ' . $proposition . "\n");
				unset($parenCount);
				unset($checkChars); // Cleanup
			}
		}

		/**
		 *
		 * Map the boolean values to each symbol for a given integer
		 *
		 */
		private function truthMapper()
		{
			$symbolIntCopy = $this->symbolInt; // Create a copy so I'm not jacking up the integer that will be used for the parsing
			while($symbolIntCopy >= 0)
			{
				$currBits = str_split(sprintf("%0".$this->bitCount."b", $symbolIntCopy)); // Break the binary string into the individual bits
				foreach($this->symbols as $idx => $symbol)
				{
					$this->symbolTruthMap[$symbolIntCopy][$symbol] = (bool)(integer)$currBits[$idx];
				}
				--$symbolIntCopy;
			}
			unset($symbolIntCopy); // Cleanup
		}

		public function showMap()
		{
			return $this->symbolTruthMap;
		}

		public function parseArgument()
		{
			foreach($this->propositions as $index => $proposition)
			{
				$returnArray['byProp'][$index]['proposition'] = $proposition; // Generates an array for storing the value of a proposition grouped by proposition
			}

			foreach($this->symbolTruthMap as $workingInt => $truthValues)
			{
				$returnArray['byInt'][$workingInt]['truthValues'] = $truthValues; // Generates an array for storing the value of a proposition grouped by int

				foreach($this->propositions as $propIndex => $proposition)
				{
					$propositionWorkingValue = $this->parseProposition($proposition, $truthValues); // Actually parsing the proposition

					$returnArray['byInt'][$workingInt]['propositions'][$propIndex] = 
						[
							'propositionValue' => $propositionWorkingValue,
							'proposition' => $proposition
						];
					$returnArray['byProp'][$propIndex]['truthValues'][$workingInt] =
						[
							'truthValues' => $truthValues,
							'propositionValue' => $propositionWorkingValue
						];
				}
			}

			//print_r($returnArray); // Debugging
			$this->currentArray = $returnArray; // Assign this latest run to $this-currentArray
			return $returnArray;
		}

		private function infixToRpn($proposition)
		{
			$proposition = preg_replace('/\s+/', '', $proposition); // Strip out whitespace
			$propChars = str_split($proposition); // This way we can analyze and operate on the specific location

			$stack	= array();
			$output	= array();

			foreach($propChars as $char)
			{
				if(in_array($char, $this->symbols))
				{
					$output[] = $char;
				}

				elseif(in_array($char, ['|', '^', '>', '~']))
				{
					if(count($stack) == 0)
					{
						$stack[] = $char;
					}

					else
					{
						$lowerOps = $this->stackLow[$char]; // Get the operators with a lower precedence
						while(TRUE)
						{
							$currStackItem = end($stack); // Peek at the top of the stack

							if((in_array($currStackItem, $lowerOps)) OR ($currStackItem === NULL) OR ($currStackItem === FALSE)) // A lower precedence operator is in the stack. Leave it there, push the input operator on the stack, and break out of the loop. That, or the current stack item is NULL or FALSE
							{
								$stack[] = $char;
								break;
							}

							else // We haven't yet seen a lower precedence item in the stack yet, so pop the stack and move it to the output
							{
								$output[] = array_pop($stack);
							}
						}
					}
				}

				elseif($char == '(')
				{
					$stack[] = $char;
				}

				elseif($char == ')')
				{
					$currStackItem = end($stack); // Peek at the top of the stack
					while($currStackItem != '(')
					{
						$output[] = array_pop($stack);
						$currStackItem = end($stack);
					}
					array_pop($stack); // Get rid of the left paren
				}
			}

			while($tempPop = array_pop($stack)) // Clean out the rest of the stack now that the expression has been run through
			{
				$output[] = $tempPop;
			}

			return $output; // Return the RPN array
		}

		/**
		 *
		 * Parses a single proposition and returns what it evaluates to
		 *
		 * @param string	$proposition The proposition to be parsed
		 * @param array		$truthSet The truthset being used for the current integer (the truth values for the symbols)
		 *
		 */
		public function parseProposition($proposition, $truthset)
		{
			/**
			 * Order of battle
			 * 1. Infix to postfix (RPN) conversion
			 * 2. Replace symbols with their boolean values
			 * 3. Parse the RPN
			 * 4. ?
			 * 5. Profit!
			 */

			// Conversion to RPN
			$rpn = $this->infixToRpn($proposition);

			// Assign the proper truth value to the symbol
			foreach($rpn as &$char)
			{
				if(in_array($char, $this->symbols))
				{
					$char = $truthset[$char];
				}
			}
			unset($char); // The reference needs to be unset, otherwise it seems the second to last element in the array becomes the last when $char is used as the value in the next foreach() loop

			foreach($rpn as $idx => $char) // Evaluate the RPN
			{
				if(is_bool($char))
				{
					$stack[] = $char;
				}

				elseif(in_array($char, ['^', '|', '>', '~']))
				{
					if($char != '~')
					{
						$secondBool	= array_pop($stack);
						$firstBool	= array_pop($stack);

						$stack[] = $this->opFunctions[$char]($firstBool, $secondBool);
					}
					else
					{
						$onlyBool = array_pop($stack);

						$stack[] = $this->opFunctions[$char]($onlyBool);
					}
				}

				//print_r($stack); // Debugging
			}

			return array_pop($stack); // Pop the last element and return higher
		}

		public function asciiTable()
		{
			// Generate the headers
			foreach($this->symbols as $symbol)
			{
				echo $symbol . "\t";
			}
			foreach($this->propositions as $proposition)
			{
				echo $proposition . "\t\t";
			}
			echo "\n\n";

			// Generate the rest of the table
			for($i = $this->symbolInt; $i >= 0; --$i)
			{
				foreach($this->currentArray['byInt'][$i]['truthValues'] as $value)
				{
					if($value)
					{
						echo 'T' . "\t";
					}
					else
					{
						echo 'F' . "\t";
					}
				}
				foreach($this->currentArray['byInt'][$i]['propositions'] as $proposition)
				{
					if($proposition['propositionValue'])
					{
						echo 'T' . "\t\t";
					}
					else
					{
						echo 'F' . "\t\t";
					}
				}

				echo "\n";
			}
		}



		public function symbolRunner()
		{
			$symbolIntCopy = $this->symbolInt;
			while($symbolIntCopy >= 0)
			{
				printf("%0".$this->bitCount."b\n", $symbolIntCopy);
				--$symbolIntCopy;
			}
		}
	}
?>