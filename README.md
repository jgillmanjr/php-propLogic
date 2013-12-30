php-propLogic
=============

My attempt at a very basic propositional logic parser in PHP

It's quite rough around the edges. Needless to say, there are severe implications of garbage in, garbage out.

Some things to note.

1. When are doing a basic binary comparison (such as A|B or A^C), don't include the parenthesis. At this point, it doesn't return the right value it seems.

However, at this time, operator precedence isn't accounted for. So if you need something like A|B|C, do it like (A|B)|C or whatever your desired precedence is.

Like I said, this is really rough. If you notice any glaring issues outside of the above, let me know.