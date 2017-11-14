import {Component, Input, OnInit} from '@angular/core';
import {JobTemplate} from "../shared/job-template";
import 'rxjs/add/operator/switchMap';
import 'rxjs/add/operator/do';
import 'rxjs/add/operator/map';
import {DateModel, DatePickerOptions} from "ng2-datepicker";
import {GetJobChart} from "../shared/messages/get-job-chart";
import {Date} from "../shared/date";
import {HttpService} from "../shared/http.service";
import {Moment} from "moment";
import {Response} from "@angular/http";
import {JobStatus} from "../shared/job-status";

interface ChartItem {
    avrDuration: number;
    avrMemory: number;
    avrWaitTime: number;
    jobsPerRange: number;
    throughput: number;
    range: number
}

interface DatasetItem {
    label: string,
    fill: boolean,
    data: Point[],
    yAxisID: number
}

interface Point {
    x: number;
    y: number;
}

@Component({
  selector: 'template-view-chart',
  template: `
<div>
  <button *ngIf="!custom" class="btn" [ngClass]="{'btn-primary': selected == 'last_hour'}" (click)="lastHour()">Last hour</button>
  <button *ngIf="!custom" class="btn" [ngClass]="{'btn-primary': selected == 'last_6_hours'}" (click)="lastSixHours()">Last 6 Hours</button>
  <button *ngIf="!custom" class="btn" [ngClass]="{'btn-primary': selected == 'last_day'}" (click)="lastDay()">Last day</button>
  <button *ngIf="!custom" class="btn" [ngClass]="{'btn-primary': selected == 'last_week'}" (click)="lastWeek()">Last week</button>
  <button *ngIf="!custom" class="btn" [ngClass]="{'btn-primary': selected == 'last_month'}" (click)="lastMonth()">Last month</button>
  <button *ngIf="!custom" class="btn" [ngClass]="{'btn-primary': selected == 'custom'}" (click)="custom = !custom">Custom</button>
  
  <div class="clearfix"></div>
  
  <form *ngIf="custom" class="form-inline">
    <label class="mr-sm-2" for="since">Since</label>
    <ng2-datepicker class="mb-2 mr-sm-2 mb-sm-0" id="since" [options]="sinceOptions" [(ngModel)]="since" name="since"></ng2-datepicker>
    
    <label class="mr-sm-2" for="until">Until</label>
    <ng2-datepicker class="mb-2 mr-sm-2 mb-sm-0" id="until" [options]="untilOptions" [(ngModel)]="until" name="until"></ng2-datepicker>
    
    <button type="button" class="btn btn-primary" (click)="customRange()">Refresh</button>
    <button type="button" class="btn" (click)="custom = false">Cancel</button>
  </form>
  
  <div *ngIf="noData" class="ft-2">No data</div>
    
  <div *ngIf="avrDurationDatasets">
    <canvas style="width: 100%; height: 400px"
            baseChart
            [datasets]="avrDurationDatasets"
            [options]="avrDurationChartOptions"
            [chartType]="'line'"
            [colors]="chartColors"
    ></canvas>
  </div>

  <div *ngIf="avrDurationDatasets">
    <canvas style="width: 100%; height: 400px"
            baseChart
            [datasets]="avrMemoryDatasets"
            [options]="avrMemoryChartOptions"
            [chartType]="'line'"
            [colors]="chartColors"
    ></canvas>
  </div>

  <div *ngIf="avrDurationDatasets">
    <canvas style="width: 100%; height: 400px"
            baseChart
            [datasets]="avrWaitTimeDatasets"
            [options]="avrWaitTimeChartOptions"
            [chartType]="'line'"
            [colors]="chartColors"
    ></canvas>
  </div>

  <div *ngIf="avrDurationDatasets">
    <canvas  style="width: 100%; height: 400px"
            baseChart
            [datasets]="jobsPerRangeDatasets"
            [options]="jobsPerRangeChartOptions"
            [chartType]="'line'"
            [colors]="chartColors"
    ></canvas>
  </div>

  <div *ngIf="throughputDatasets">
    <canvas  style="width: 100%; height: 400px"
             baseChart
             [datasets]="throughputDatasets"
             [options]="throughputOptions"
             [chartType]="'line'"
             [colors]="chartColors"
    ></canvas>
  </div>
</div>
  `
})
export class TemplateViewChartComponent implements OnInit {
  @Input() jobTemplate: JobTemplate;

  since: DateModel;
  sinceOptions: DatePickerOptions;
  until: DateModel;
  untilOptions: DatePickerOptions;
  custom: boolean;
  noData: boolean;
  selected: 'last_hour' | 'last_6_hours' | 'last_day' | 'last_week' | 'last_month' | 'custom';

  avrDurationDatasets: DatasetItem[];
  avrDurationChartOptions: any;

  avrMemoryDatasets: DatasetItem[];
  avrMemoryChartOptions: any;

  avrWaitTimeDatasets: DatasetItem[];
  avrWaitTimeChartOptions: any;

  jobsPerRangeDatasets: DatasetItem[];
  jobsPerRangeChartOptions: any;

  throughputDatasets: DatasetItem[];
  throughputOptions: any;

  chartColors: Array<any> = [{ // dark grey
    backgroundColor: 'rgba(77,83,96,0.2)',
    borderColor: 'rgba(77,83,96,1)',
    pointBackgroundColor: 'rgba(77,83,96,1)',
    pointBorderColor: '#fff',
    pointHoverBackgroundColor: '#fff',
    pointHoverBorderColor: 'rgba(77,83,96,1)'
  }];

  constructor(private httpService: HttpService) {
    let moment = require('moment');

    this.sinceOptions = new DatePickerOptions();
    this.sinceOptions.initialDate = moment().subtract(7 * 24, 'hours').toDate();

    this.untilOptions = new DatePickerOptions();
    this.untilOptions.initialDate = moment().toDate();

    this.avrDurationChartOptions = this.createChartOptions('Duration', 'Duration (ms)');
    this.avrMemoryChartOptions = this.createChartOptions('Memory', 'Memory');
    this.avrWaitTimeChartOptions = this.createChartOptions('Wait time', 'Wait time');
    this.jobsPerRangeChartOptions = this.createChartOptions('Executed jobs', 'Jobs');
    this.throughputOptions = this.createChartOptions('Throughput', 'jobs/h');

    this.custom = false;
  }

  ngOnInit(): void {
    this.lastSixHours();
  }

  customRange(): void {
    this.selected = 'custom';
    this.refresh(this.since.momentObj, this.until.momentObj);
  }

  lastHour(): void {
    this.selected = 'last_hour';
    let moment = require('moment');

    this.refresh(moment().subtract(1, 'hour'), moment());
  }

  lastSixHours(): void {
    this.selected = 'last_6_hours';
    let moment = require('moment');

    this.refresh(moment().subtract(6, 'hour'), moment());
  }

  lastDay(): void {
    this.selected = 'last_day';
    let moment = require('moment');

    this.refresh(moment().subtract(1, 'day'), moment());
  }

  lastWeek(): void {
    this.selected = 'last_week';
    let moment = require('moment');

    this.refresh(moment().subtract(1, 'week'), moment());
  }

  lastMonth(): void {
    this.selected = 'last_month';
    let moment = require('moment');

    this.refresh(moment().subtract(1, 'month'), moment());
  }

  private createChartOptions(title: string, yAxeTitle: string) {
    return {
      responsive: true,
      title: {
        display:true,
        text: title
      },
      scales: {
        xAxes: [{
          type: "time",
          distribution: 'linear',
          display: true,
          time: {
            format: 'x',
            tooltipFormat: 'll HH:mm',
            max: null,
            min: null,
          },
          scaleLabel: {
            display: true,
            labelString: 'Date'
          }
        }],
        yAxes: [{
          id: 1,
          display: true,
          scaleLabel: {
            display: true,
            labelString: yAxeTitle
          }
        },]
      }
    };
  }

  private refresh(since: Moment, until: Moment)
  {
    this.sinceOptions.initialDate = since.toDate();
    this.untilOptions.initialDate = until.toDate();

    this.jobsPerRangeDatasets = null;
    this.avrDurationDatasets = null;
    this.avrMemoryDatasets = null;
    this.avrWaitTimeDatasets = null;
    this.noData = false;

    const getJobChart = new GetJobChart(
      this.jobTemplate.templateId,
      Date.fromMoment(since),
      Date.fromMoment(until),
    );
    getJobChart.statuses = [
        JobStatus.COMPLETED,
        JobStatus.FAILED,
        JobStatus.TERMINATED,
        JobStatus.CANCELED
    ];

    this.httpService.post('/api/metrics/chart', getJobChart)
      .map((res: Response) => res.json().chart as ChartItem[])
      .subscribe((chart: ChartItem[]) => {
        let avrDurationDatasets: DatasetItem[] = [{
          label: "Duration (ms)",
          fill: false,
          data: [],
          yAxisID: 1
        }];

        let avrMemoryDatasets: DatasetItem[] = [{
          label: "Memory (Mb)",
          fill: false,
          data: [],
          yAxisID: 1
        }];
        let avrWaitTimeDatasets: DatasetItem[] = [{
          label: "Wait time (ms)",
          fill: false,
          data: [],
          yAxisID: 1
        }];
        let jobsPerRangeDatasets: DatasetItem[] = [{
          label: "Executed jobs",
          fill: false,
          data: [],
          yAxisID: 1
        }];
        let throughputDatasets: DatasetItem[] = [{
          label: "jobs/h",
          fill: false,
          data: [],
          yAxisID: 1
        }];

        if (chart.length) {
          chart.forEach((item: ChartItem) => {
            const time = item.range * 1000;
            avrDurationDatasets[0].data.push({x: time, y: item.avrDuration});
            avrMemoryDatasets[0].data.push({x: time, y: item.avrMemory / 1000000});
            avrWaitTimeDatasets[0].data.push({x: time, y: item.avrWaitTime});
            jobsPerRangeDatasets[0].data.push({x: time, y: item.jobsPerRange});
            throughputDatasets[0].data.push({x: time, y: item.jobsPerRange});
          });

          avrDurationDatasets[0].data.push({x: parseInt(until.format('x')), y: null});
          avrDurationDatasets[0].data.push({x: parseInt(since.format('x')), y: null});
          avrMemoryDatasets[0].data.push({x: parseInt(until.format('x')), y: null});
          avrMemoryDatasets[0].data.push({x: parseInt(since.format('x')), y: null});
          avrWaitTimeDatasets[0].data.push({x: parseInt(until.format('x')), y: null});
          avrWaitTimeDatasets[0].data.push({x: parseInt(since.format('x')), y: null});
          jobsPerRangeDatasets[0].data.push({x: parseInt(until.format('x')), y: null});
          jobsPerRangeDatasets[0].data.push({x: parseInt(since.format('x')), y: null});
          throughputDatasets[0].data.push({x: parseInt(until.format('x')), y: null});
          throughputDatasets[0].data.push({x: parseInt(since.format('x')), y: null});


          this.avrDurationDatasets = avrDurationDatasets;
          this.avrMemoryDatasets = avrMemoryDatasets;
          this.avrWaitTimeDatasets = avrWaitTimeDatasets;
          this.jobsPerRangeDatasets = jobsPerRangeDatasets;
          this.throughputDatasets = throughputDatasets;
        } else {
          this.noData = true;
        }
      })
    ;
  }
}