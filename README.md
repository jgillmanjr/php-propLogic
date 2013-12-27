php-propLogic
=============

My attempt at a very basic propositional logic parser in PHP

It's rough around the edges. Severe implications of garbage in, garbage out.

I'd also recommend using parenthesis since I haven't done anything to ensure operator precedence. So instead of A|B|C, pass in (A|B)|C . In fact, for simple negations, do (~A). A|B|C shouldn't even work, or even just A|B. Pairs. (~A) or (A|B).