/*
|--------------------------------------------------------------------------
| Chart.js Register
|--------------------------------------------------------------------------
|
| Regista todos os componentes utilizados pela aplicação.
| Este ficheiro deve ser o único responsável por importar o Chart.js.
|
*/

import {
    Chart,

    CategoryScale,
    LinearScale,

    BarController,
    BarElement,

    LineController,
    LineElement,
    PointElement,

    DoughnutController,
    ArcElement,

    Tooltip,
    Legend,

    Title,

    Filler
} from "chart.js";

/*
|--------------------------------------------------------------------------
| Registo Global
|--------------------------------------------------------------------------
*/

Chart.register(

    CategoryScale,
    LinearScale,

    BarController,
    BarElement,

    LineController,
    LineElement,
    PointElement,

    DoughnutController,
    ArcElement,

    Tooltip,
    Legend,

    Title,

    Filler

);

/*
|--------------------------------------------------------------------------
| Export
|--------------------------------------------------------------------------
*/

export default Chart;
