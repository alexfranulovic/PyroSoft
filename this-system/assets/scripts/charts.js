import {
    Chart,
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    BarController,
    BubbleController,
    DoughnutController,
    LineController,
    PieController,
    PolarAreaController,
    RadarController,
    ScatterController,
    CategoryScale,
    LinearScale,
    LogarithmicScale,
    RadialLinearScale,
    TimeScale,
    TimeSeriesScale,
    Decimation,
    Filler,
    Legend,
    Title,
    Tooltip,
    SubTitle
} from 'chart.js';

Chart.register(
    ArcElement,
    LineElement,
    BarElement,
    PointElement,
    BarController,
    BubbleController,
    DoughnutController,
    LineController,
    PieController,
    PolarAreaController,
    RadarController,
    ScatterController,
    CategoryScale,
    LinearScale,
    LogarithmicScale,
    RadialLinearScale,
    TimeScale,
    TimeSeriesScale,
    Decimation,
    Filler,
    Legend,
    Title,
    Tooltip,
    SubTitle
);

var defaultColors = [
    '#009159',
    '#962899',
    '#ff8a00',
    '#c0c0c0',
    '#154499'
];

var dassColors = [
    '#198754',
    '#ffc107',
    '#ffc107',
    '#dc3545',
    '#dc3545',
];

var ctx = false;
var reasonTherapy = document.getElementById('Top5ReasonTherapy');

if(reasonTherapy) {
    var reasonCtx = reasonTherapy.getContext('2d');
    var reasonLabels = JSON.parse(reasonTherapy.dataset.labels);
    var reasonNumbers = JSON.parse(reasonTherapy.dataset.numbers);

    var Top5ReasonTherapy = new Chart(reasonCtx, {
        type: 'doughnut',
        data: {
            labels: reasonLabels,
            datasets: [{
                data: reasonNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: defaultColors,
            }]
        }
    });
}

var typeTherapy = document.getElementById('Top5TypeTherapy');
if(typeTherapy) {
    ctx = typeTherapy.getContext('2d');
    var typeLabels = JSON.parse(typeTherapy.dataset.labels);
    var typeNumbers = JSON.parse(typeTherapy.dataset.numbers);

    var Top5TypeTherapy = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{
                data: typeNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: defaultColors,
            }]
        }
    });
}

var typeTherapist = document.getElementById('Top5TypeTherapist');
if(typeTherapist) {
    ctx = typeTherapist.getContext('2d');
    var authorLabels = JSON.parse(typeTherapist.dataset.labels);
    var authorNumbers = JSON.parse(typeTherapist.dataset.numbers);

    var Top5TypeTherapist = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: authorLabels,
            datasets: [
            {
                label: "Terapias agendadas",
                data: authorNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: defaultColors,
            }]
        },
        options: {
            scales: {
                y: {
                    min: 0,
                    ticks: {
                      stepSize: 1
                    }
                }
            },
            responsive: true,
        }
    });
}

var doughnutPercentProgress = document.getElementById('doughnutPercentProgress');
if(doughnutPercentProgress) {
    ctx = doughnutPercentProgress.getContext('2d');

    var doughnutChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Red', 'Blue'],
            datasets: [{
                data: [30, 30],
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: ['#962899', '#c0c0c0'],
            }]
        },
        options: {
            cutout: '90%',
            plugins: {
                legend: false,
            },
            //responsive: true,
        }
    });
}

var emotionsCounter = document.getElementById('emotionsCounter');
if(emotionsCounter) {
    ctx = emotionsCounter.getContext('2d');
    var emojis = JSON.parse(emotionsCounter.dataset.labels);
    var typeNumbers = JSON.parse(emotionsCounter.dataset.numbers);
    var emojis = JSON.parse(emotionsCounter.dataset.emojis);
    var emojis = ['😔', '😡', '😐', '😌', '😃']

    var Top5TypeTherapy = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: emojis,
            datasets: [{
                data: typeNumbers,
                borderWidth: 1,
                backgroundColor: defaultColors,
            }]
        },
        options: {
            plugins: {
                legend: false,
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            var index = context.dataIndex;
                            var emotionsList = ['😔', '😡', '😐', '😌', '😃'];
                            var emotionLabel = emotionsList[index];
                            var count = context.dataset.data[index];
                            return '\nContador: ' + count;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        font: {
                            size: 18,
                        },
                    }
                },
                y: {
                    min: 0,
                    ticks: {
                      stepSize: 1,
                    }
                }
            },
            responsive: true,
        }
    });
}

var emotionsProgress = document.getElementById('emotionsProgress');
if(emotionsProgress) {
    ctx = emotionsProgress.getContext('2d');
    var typeLabels = JSON.parse(emotionsProgress.dataset.labels);
    var typeNumbers = JSON.parse(emotionsProgress.dataset.numbers);
    var emojis = JSON.parse(emotionsProgress.dataset.emojis);

    function mapValueToLabel(value) {
        switch (value) {
            case 0:
                return '😔';
            case 1:
                return '😡';
            case 2:
                return '😐';
            case 3:
                return '😌';
            case 4:
                return '😃';
            default:
                return '';
        }
    }

    var Top5TypeTherapy = new Chart(ctx, {
        type: 'line',
        data: {
            labels: typeLabels,
            datasets: [{
                label: 'Seu humor estava',
                data: typeNumbers,
                borderColor: ['#962899'],
                backgroundColor: ['#962899'],
                borderWidth: 2,
                pointStyle: function (context) {
                    var index = context.dataIndex;
                    var value = context.dataset.data[index];
                    return emojis[value]; // Obtém o emoji correspondente ao estado emocional
                }
            }]
        },
        options: {
            plugins: {
                legend: false,
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var value = context.raw;
                            var label = mapValueToLabel(value);
                            return 'Estado emocional: ' + label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        stepSize: 1,
                        max: 4,
                        min: 0,
                        callback: function(value, index, values) {
                            return emojis[value]; // Exibe emojis no eixo y
                        },
                        font: {
                            size: 18,
                        },
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 0,
                        autoSkip: true
                    }
                }
            },
        }
    });
}

var SchedulingTax = document.getElementById('SchedulingTax');
if(SchedulingTax) {
    ctx = SchedulingTax.getContext('2d');
    var authorLabels = JSON.parse(SchedulingTax.dataset.labels);
    var authorNumbers = JSON.parse(SchedulingTax.dataset.numbers);

    var SchedulingTax = new Chart(ctx, {
        type: 'line',
        data: {
            labels: authorLabels,
            datasets: [{
                label: "Porcentagem da taxa de agendamento",
                data: authorNumbers,
                borderColor: ['#962899'],
                backgroundColor: ['#962899'],
            }]
        },
        options: {
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toFixed(1) + '%';
                            return label;
                        }
                    }
                },
                legend: false,
            },
            scales: {
                y: {
                    min: 0,
                    ticks: {
                      stepSize: 1
                    }
                }
            },
            responsive: true,
        }
    });
}

var TimelineContributor = document.getElementById('TimelineContributorActive');
if(TimelineContributor) {
    ctx = TimelineContributor.getContext('2d');
    var authorLabels = JSON.parse(TimelineContributor.dataset.labels);
    var authorNumbers = JSON.parse(TimelineContributor.dataset.numbers);

    var TimelineContributorActive = new Chart(ctx, {
        type: 'line',
        data: {
            labels: authorLabels,
            datasets: [{
                label: "Colaboradores ativos",
                data: authorNumbers,
                borderColor: ['#962899'],
                backgroundColor: ['#962899'],
            }]
        },
        options: {
            plugins: {
                legend: false,
            },
            scales: {
                y: {
                    min: 0,
                    ticks: {
                      stepSize: 1
                    }
                }
            },
            responsive: true,
        }
    });
}

var timelineScheduling = document.getElementById('TimelineSchedulingTherapy');
if(timelineScheduling) {
    ctx = timelineScheduling.getContext('2d');
    var authorLabels = JSON.parse(timelineScheduling.dataset.labels);
    var authorNumbers = JSON.parse(timelineScheduling.dataset.numbers);

    var TimelineSchedulingTherapy = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: authorLabels,
            datasets: [
            {
                label: "Terapias agendadas",
                data: authorNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: ['#009159'],
            }]
        },
        options: {
            plugins: {
                legend: false,
            },
            scales: {
                y: {
                    min: 0,
                    ticks: {
                      stepSize: 1
                    }
                }
            },
            responsive: true,
        }
    });
}

var generalViewDass = document.getElementById('generalViewDass');
if(generalViewDass) {
    ctx = generalViewDass.getContext('2d');
    var authorLabels = JSON.parse(generalViewDass.dataset.labels);
    var authorNumbers = JSON.parse(generalViewDass.dataset.numbers);
    var authorDate = generalViewDass.dataset.date;
    var colors = JSON.parse(generalViewDass.dataset.colors);

    var generalViewDass = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: authorLabels,
            datasets: [{
                label: authorDate,
                data: authorNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: colors,
                borderRadius: 10,
            }]
        },
        options: {
            plugins: {
                legend: false,
            },
            responsive: true,
            scales: {
                y: {
                    min: 0,
                    max: 42,
                    ticks: {
                      stepSize: 6
                    }
                }
            }
        }
    });
}

var depressionBar = document.getElementById('depressionBar');
if(depressionBar) {
    ctx = depressionBar.getContext('2d');
    var authorLabels = JSON.parse(depressionBar.dataset.labels);
    var authorNumbers = JSON.parse(depressionBar.dataset.numbers);

    var depressionBar = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: authorLabels,
            datasets: [{
                label: "Colaboradores",
                data: authorNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: dassColors,
            }]
        },
        options: {
            plugins: {
                legend: false,
            },
            responsive: true,
        }
    });
}

var anxietyBar = document.getElementById('anxietyBar');
if(anxietyBar) {
    ctx = anxietyBar.getContext('2d');
    var authorLabels = JSON.parse(anxietyBar.dataset.labels);
    var authorNumbers = JSON.parse(anxietyBar.dataset.numbers);

    var anxietyBar = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: authorLabels,
            datasets: [{
                label: "Colaboradores",
                data: authorNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: dassColors,
            }]
        },
        options: {
            plugins: {
                legend: false,
            },
            responsive: true,
        }
    });
}

var stressBar = document.getElementById('stressBar');
if(stressBar) {
    ctx = stressBar.getContext('2d');
    var authorLabels = JSON.parse(stressBar.dataset.labels);
    var authorNumbers = JSON.parse(stressBar.dataset.numbers);

    var stressBar = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: authorLabels,
            datasets: [{
                label: "Colaboradores",
                data: authorNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: dassColors
            }]
        },
        options: {
            plugins: {
                legend: false,
            },
            responsive: true,
        }
    });
}

var depressionDoug = document.getElementById('depressionDoug');
if(depressionDoug) {
    ctx = depressionDoug.getContext('2d');
    var typeLabels = JSON.parse(depressionDoug.dataset.labels);
    var typeNumbers = JSON.parse(depressionDoug.dataset.numbers);

    var Top5TypeTherapy = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{
                data: typeNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: dassColors
            }]
        },
        options: {
            cutout: '70%',
        }
    });
}

var anxietyDoug = document.getElementById('anxietyDoug');
if(anxietyDoug) {
    ctx = anxietyDoug.getContext('2d');
    var typeLabels = JSON.parse(anxietyDoug.dataset.labels);
    var typeNumbers = JSON.parse(anxietyDoug.dataset.numbers);

    var Top5TypeTherapy = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{
                data: typeNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: dassColors
            }]
        },
        options: {
            cutout: '70%',
        }
    });
}

var stressDoug = document.getElementById('stressDoug');
if(stressDoug) {
    ctx = stressDoug.getContext('2d');
    var typeLabels = JSON.parse(stressDoug.dataset.labels);
    var typeNumbers = JSON.parse(stressDoug.dataset.numbers);

    var Top5TypeTherapy = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{
                data: typeNumbers,
                borderWidth: 1,
                borderColor: 'white',
                backgroundColor: dassColors
            }]
        },
        options: {
            cutout: '70%',
        }
    });
}
