SELECT * FROM transactions
INNER JOIN batches ON batches.id = transactions.batch_id AND batches.ref_num = '235116271768102557430611' AND batches.date = '2018-05-05'
INNER JOIN merchants ON merchants.id = batches.merchant_id AND merchant_id = '112232864445369976'
INNER JOIN card_types ON card_types.id = transactions.card_type_id
INNER JOIN transaction_types ON transaction_types.id = transactions.type_id;


SELECT card_types.name, COUNT(*), SUM(transactions.amount)  FROM transactions
INNER JOIN batches ON batches.id = transactions.batch_id
AND batches.ref_num = '235116271768102557430611' AND batches.date = '2018-05-05'
INNER JOIN merchants ON merchants.id = batches.merchant_id
AND merchant_id = '112232864445369976'
INNER JOIN card_types ON card_types.id = transactions.card_type_id
INNER JOIN transaction_types ON transaction_types.id = transactions.type_id

GROUP BY card_types.name;

SELECT merchants.name, COUNT(*), SUM(transactions.amount) FROM transactions
INNER JOIN batches ON batches.id = transactions.batch_id
INNER JOIN merchants ON merchants.id = batches.merchant_id
AND merchant_id = '112232864445369976'
INNER JOIN card_types ON card_types.id = transactions.card_type_id
INNER JOIN transaction_types ON transaction_types.id = transactions.type_id
WHERE transactions.date BETWEEN '2018-05-04' and '2018-05-04'
GROUP BY merchants.id;

SELECT
	merchants. NAME,
	COUNT(*),
	SUM(transactions.amount) as total
FROM
	transactions
INNER JOIN batches ON batches.id = transactions.batch_id
INNER JOIN merchants ON merchants.id = batches.merchant_id
INNER JOIN card_types ON card_types.id = transactions.card_type_id
INNER JOIN transaction_types ON transaction_types.id = transactions.type_id
WHERE
	transactions.date BETWEEN '2018-05-04' AND '2018-05-04'
GROUP BY
	merchants.id
ORDER BY total DESC
LIMIT 10;