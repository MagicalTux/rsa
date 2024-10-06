<?php

function large_prime($bits, $diff = null, $min = 0) {
	if (($bits & 7) == 0) {
		// bits is a multiple of 8
		$bitmask = null;
		$bytes = $bits >> 3;
	} else {
		$bitmask = gmp_sub(gmp_pow(2, $bits), 1); // ensures the number of bits
		$bytes = (int)ceil($bits/8);
	}

	while(true) {
		$val = random_bytes((int)ceil($bits/8));
		$big = gmp_import($val);
		if (!is_null($bitmask)) $big = gmp_and($big, $bitmask);
		if (gmp_prob_prime($big) == 0) continue;
		if ((!is_null($diff)) && (gmp_cmp($diff, $big) == 0)) continue; // equal to $diff → skip
		if (gmp_cmp($big, $min) < 0) continue;
		return $big;
	}
}

echo "Easy to understand RSA with small values!\n\n";

// We choose prime numbers with a minimum of 10 so that at the very least our value for $n will be >100, which
// means we can encrypt any value in the 0~100 range.

$bits = 8; // how many bits maximum we want in n
$p = large_prime($bits/2, min: 7);
$q = large_prime($bits/2, $p, min: 7);

show_demo(gmp_init($p), gmp_init($q));

function show_demo($p, $q) {
	echo "First, choose two prime numbers p and q\n";
	echo "p = $p\n";
	echo "q = $q\n";

	$n = gmp_mul($p, $q); echo "Then compute n = p * q = $p * $q = $n\n";

	// compute lambda_n, which is the lcm between $p-1 and $q-1
	$lambda_n = gmp_lcm(gmp_sub($p, 1), gmp_sub($q, 1));
	echo "λ(n) = $lambda_n (Carmichael function, λ(n) is the smallest m for a^m=1(mod n))\n";

	echo "Find e such as the gcd of e and λ(n) is 1\n";
	// compute smallest value for e to keep math simple
	foreach([2,3,5,7,11,13,17,19,23] as $prime) {
		if (gmp_cmp(gmp_gcd($prime, $lambda_n), 1) == 0) {
			$e = $prime;
			echo "e = $e\n";
			break;
		}
	}
	$d = gmp_invert($e, $lambda_n);
	echo "d ≡ e^−1 (mod λ(n)), so d = invert power(e, λ(n)) = $d\n";
	echo "\n";
	echo "Public key: [e, n] = [$e, $n]\n";
	echo "Private key [d, n] = [$d, $n]\n";

	// testing our implementation
	echo "\n";
	echo "Encrypting message 42 so only the private key (d) can read it\n";

	$encrypted = gmp_powm(42, $e, $n);
	echo "pow(42, e) mod n = pow(42, $e) mod $n = $encrypted\n";

	echo "The result, $encrypted, can only be reverted to 42 by the holder of the private key:\n";
	$decrypted = gmp_powm($encrypted, $d, $n);
	echo "pow($encrypted, d) mod n = pow($encrypted, $d) mod $n = $decrypted\n";
}
