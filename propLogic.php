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
		private	$symbolInt, $operatorList, $logicalOperators, $symbolTruthMap, $validFirstChars;


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
			$this->symbolInt = (pow(2,$this->bitCount) - 1); // Set the symbol integer to the top

			$this->symbols = $symbols;
			$this->propositions = $propositions;

			// Logical Operators
			$this->operatorList[] = '~'; // Negation
			$this->operatorList[] = '>'; // Material Implication
			$this->operatorList[] = '^'; // Conjunction
			$this->operatorList[] = '|'; // Inclusive Or
			$this->operatorList[]	= '('; // Begin Grouping
			$this->operatorList[] = ')'; // End Grouping

			$this->logicalOperators['~']	=
				function($boolean)
				{
					return !$boolean; // Just a simple negation
				};

			$this->logicalOperators['>']	=
				function($firstBoolean, $secondBoolean)
				{
					if(($firstBoolean == TRUE) AND ($secondBoolean == FALSE))
					{
						return FALSE;
					}
					else
					{
						return TRUE;
					}
				};

			$this->logicalOperators['^']	=
				function($firstBoolean, $secondBoolean)
				{
					return ($firstBoolean AND $secondBoolean);
				};

			$this->logicalOperators['|']	=
				function($firstBoolean, $secondBoolean)
				{
					return ($firstBoolean OR $secondBoolean);
				};

			// End Logical Operators

			$this->sanityCheck(); // Verify we don't have characters that fall outside of the logicalElements array
			$this->truthMapper(); // Create the array mapping the symbol boolean values for all the integers possible
		}

		private function sanityCheck() // Does a character check for every proposition
		{
			// Accepted input - comprised of the logic operators as well as the symbols
			foreach($this->operatorList as $operator)
			{
				$validElements[] = $operator;
			}

			foreach($this->symbols as $symbol)
			{
				$validElements[] = $symbol;
				$this->validFirstChars[] = $symbol; // This is to indicate that symbols are valid first chars in a prop
			}
			// End Accepted Input

			// Additional valid first chars
			$this->validFirstChars[] = '~';
			$this->validFirstChars[] = '(';
			// End valid first chars

			foreach($this->propositions as $proposition) 
			{
				$tempArray = str_split($proposition);

				if(!in_array($tempArray[0], $this->validFirstChars)) // Run a quick check to make sure the first character is valid
				{
					exit("Proposition has invalid form\n");
				}

				foreach($tempArray as $idx => $char)
				{
					if($char != " ") // Ignore Spaces
					{
						if(!in_array($char, $validElements)) // Validate the characters in use are valid elements
						{
							exit('Illegal Character: ' . $char . "\n");
						}
					}

					// Parenthesis balance check
					if($char == '(')
					{
						$openParens++;
					}
					if($char == ')')
					{
						$openParens--;
					}
					// End Parenthesis balance check
				}
				if($openParens != 0) // We have a mismatch
				{
					exit("Parenthesis mismatch\n");
				}
			}
			unset($validElements); // Cleanup			
		}

		/**
		 *
		 * Map the boolean values to each symbol for a given integer
		 *
		 */
		private function truthMapper()
		{
			while($this->symbolInt >= 0)
			{
				$currBits = str_split(sprintf("%0".$this->bitCount."b", $this->symbolInt)); // Break the binary string into the individual bits
				foreach($this->symbols as $idx => $symbol)
				{
					$this->symbolTruthMap[$this->symbolInt][$symbol] = (bool)(integer)$currBits[$idx];
				}
				--$this->symbolInt;
			}
		}

		public function showMap()
		{
			return $this->symbolTruthMap;
		}

		public function generateTable()
		{
			/**
			 * Order of battle
			 * 1. Look at integer and use to determine binary (truth) values for symbols
			 * 2. For each proposition, go as deep as possible and work out
			 * 3. ?
			 * 4. Profit!
			 */
			foreach($this->symbolTruthMap as $workingInt => $truthValues)
			{
				foreach($this->propositions as $proposition)
				{
					foreach($truthValues as $symbol => $value) // Debugging
					{
						echo $symbol . " is " . $value . "\n";
					}

					$this->parseProposition($proposition, $truthValues);
				}
			}
		}

		/**
		 *
		 * Parses a single proposition and returns what it evaluates to
		 *
		 * @param string	$proposition The proposition to be parsed
		 * @param array		$truthSet The truthset being used for the current integer (the truth values for the symbols)
		 *
		 */
		public function parseProposition($proposition, $truthSet)
		{
			$proposition = preg_replace('/\s+/', '', $proposition); // Whitespace? Ain't nobody got no time for that!
			$propChars = str_split($proposition); // Break the proposition down into its base elements - the character

			// Map where the groups start and stop and the level
			$levelIdx = 0; // Main level
			$groupMap = Array();// Mark the start and end location(s) of a level via the character index of a string. Parenthesis locations will be the ones tracked
			$groupMap[$levelIdx] = ['start' => 0, 'end' => (count($propChars) - 1)]; // Main level will always  start at 0 and end at the character count - 1 (this gives the end index)
			$levelMarker = Array(); // Used to track the last array index for a level when going deeper and coming out if multiple groups exist at that level

			foreach($propChars as $idx => $char)
			{
				if($char == '(')
				{
					++$levelIdx; // Go down a level
					$groupMap[$levelIdx][] = ['start' => $idx];
					$levelMarker[$levelIdx] = (count($groupMap[$levelIdx]) - 1);
				}
				if($char == ')')
				{
					$groupMap[$levelIdx][$levelMarker[$levelIdx]]['end'] = $idx;
					--$levelIdx; // Go up a level
				}
			}
			$groupMap = array_reverse($groupMap, TRUE); // Reverse this but preserve keys since we will be working inside out

			//print_r($groupMap);//Debugging
			// End group mapping

			// Truth value assignments
			$workingTruthBox = $propChars; // Copy over the array of characters for transformation into truth values
			foreach($workingTruthBox as $idx => $char)
			{
				if(in_array($char, $this->symbols))
				{
					$workingTruthBox[$idx] = $truthSet[$char]; // Replace the character with its truth value
				}
			}
			// End truth value assignments

			// Some block here, not sure what will do yet..
			foreach($groupMap as $groupLevel => $groupLevelContents) // Eval the groups at each level, returning to the one higher when it gets evaled
			{
				//echo "Working on group level: " . $groupLevel . "\n"; // Debugging
				if($groupLevel != 0) // The main group will have different rules
				{
					foreach($groupLevelContents as $glcIdx => $endGroup)
					{
						echo "Working on group level: " . $groupLevel . " and group " . $glcIdx . "\n"; // Debugging
						$groupLength = (($endGroup['end'] - 1) - ($endGroup['start'] + 1)) + 1; // Adjusting for the fact that the start and end locations are where the parens are at, in addition, the +1 is in the event there is only one element inside
						//$groupData = array_slice($workingTruthBox, $endGroup['start'] + 1, $groupLength, TRUE);

						for($k = ($endGroup['start'] + 1); $k <= ($endGroup['end'] - 1); ++$k) // Because slice didn't seem to be working..
						{
							if(isset($workingTruthBox[$k])) // This will skip over indexes that may not be there anymore due to previous runs/modifications
							{
								$groupData[$k] = $workingTruthBox[$k];
								//echo "Importing data at index " . $k ."\n"; // Debugging
							}
							else // Debugging
							{
								//echo "Key " . $k . " has already been 86'd\n";
							}
						}
						//echo "----------------------------\n"; // Debugging
						//echo "Working on the following characters:\n"; //Debugging
						//print_r($groupData); // Debugging
						reset($groupData); // Reset before we use it
						print_r($groupData); // Debugging
						while($currentData = each($groupData)) // Using each() instead of current() as to distinguish between end of array and boolean false value
						{
							prev($groupData); // Since each automatically increments
							$currentData = $currentData['value']; // Because each() returns an array

							if(is_string($currentData)) // At this point, the only strings should be the logical operators
							{
								if($currentData == '~')
								{
									$nextData = next($groupData);
									$result = $this->logicalOperators['~']($nextData); // Negate the following bit o' data
									prev($groupData); // Pointer Recovery
								}
								else
								{
									$previousData = prev($groupData);
										next($groupData); // Pointer Recovery
									$nextData = next($groupData);
										prev($groupData); // Pointer Recovery
									$result = $this->logicalOperators[$currentData]($previousData, $nextData); // Compare the bits of data before and after the operator
								}
								

								echo "Setting index " . $endGroup['start'] . " to " . $result . "\n"; // Debugging
								for($i = $endGroup['start'] + 1; $i <= $endGroup['end']; ++$i)
								{
									if(isset($workingTruthBox[$i])) // This way we don't have PHP complaining about compacting things that already got the works
									{
										unset($workingTruthBox[$i]);
									}
								}
								// Replace the group start with the result, and nuke the rest of the range (compact essentially)
								$workingTruthBox[$endGroup['start']] = $result;
							}
							next($groupData);
						}
						unset($groupData); // Cleanup
					}
				}
				/*else // Level 0
				
				}*/
			}
			// End some block

			//print_r($groupMap); // Debugging
			echo "Level 0 left at:\n"; // Debugging
			print_r($workingTruthBox); // Debugging
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