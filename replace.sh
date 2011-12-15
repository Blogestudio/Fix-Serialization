echo 'Replacing ...';

# Enum sql files
for f in *.sql;	do
	echo 'File:' $f;
	sed 's/mydomain\.com/mynewdomain\.com/g' $f > $f.tmp; mv $f.tmp $f;
done

echo 'Fix serialization ...';

# Fix serialized length strings
for f in *.sql;	do
	echo 'File:' $f;
	/usr/bin/php fix-serialization.php $f
done

echo 'Done!';