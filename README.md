# Description
Tester code of the recommendor system by counting the frequency of other items appearing together with the focus item in the sale record.
For example, if there's a sale record with Item A,B,C in the same receipt. When we use A as the focus target, B and C should appear as the result. 

Part of the code will process the sale record to make a score table looking like this -> Item X : [Item Y: (scores of Y)]

Product A: [B:10,C:8,D:4] -- meaning, Product A has been paired with B 10 times, C 8 times, D 4 times

Product B: [A:10,C:4,D:9] -- meaning, Product B has been paired with A 10 times, C 4 times, D 9 times

The score table such as above then will be use by further calculation methods below.

------

1.Simple add-up
- Counting the frequency as is -- the score of each item is as high as how much they appear together with the focus item. 
- If the focus item is a set of multiple, the score list of both item are summed together for the final result

For example,

If we use [A] as Focal point, [B:10],[C:8],[D:4] will be the result

If we use [A,B] then we will have [C:12],[D:13] as the result

2.Give priority to duplicate items
- Just like #1. But when focus on set of multiple items, result items which exist on multiple will be given higher priority even if the result score is lower

For example,

If we also have

Product E: [C:1,D:2,F:50]

Then if we use [A,B,E], we will have [C:13,3],[D:15,2],[F:50,1] where the 2nd number in the array is the priority level. Since C appears on all 3 items result list, C would be the first candidate when we try to recommend something with [A,B,E] as the focus, followed by [D] and [F] despite [F] having a very high score of 50.

3.Create score list not for indivual items but for each items combination in the sale record.
-Unlike the 2 methods above, this method use another score table

For example, if we have a sale record of [A,B,C],[A,D,E]

the pre-calculated score table will look like this

[A]       : [B],[C],[D],[E]

[A,B]     : [C]

[A.C]     : [B]

[A,D]     : [E]

[A,B,C]   : []

[A,B,D]   : []

[A,D,C]   : []

[A,D,E]   : []

[B] ...

-There's no need of further calculation for this method. The result can be fetched right away from the score table.
