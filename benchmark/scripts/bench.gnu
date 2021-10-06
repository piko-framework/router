# gnuplot 5.2

set ylabel "time (µs)"
set style data histogram
set style histogram cluster
set boxwidth 0.9 relative
set style fill solid
set xtic rotate by 60 right offset 0.0,-1.0
set offset 0,1,0,0
set bmargin 14
set key autotitle columnheader
set size 1.0, 1.0
set term png size 1280, 720

# Bench Static/Dynamic routes
plot "benchmark.csv" every ::0::5 using 3:xtic(2) title "Symfony-router" lc rgb 'red', "benchmark.csv" every ::6::11 using 3:xtic(2) title "Piko-router", "benchmark.csv" every ::12::17 using 3:xtic(2) title "Fastroute"
