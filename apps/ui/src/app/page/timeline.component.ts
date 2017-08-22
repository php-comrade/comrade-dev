import {Component, Input, OnInit} from '@angular/core';
import {TimelineService} from "../shared/timeline.service";
import {GetTimeline} from "../shared/messages/get-timeline";
import {Job} from "../shared/job";
import {Observable} from "rxjs/Observable";
import 'rxjs/add/observable/interval';

@Component({
  selector: 'timeline',
  templateUrl: './timeline.component.html',
})

export class TimelineComponent implements OnInit {
  @Input() jobTemplateId: string;
  @Input() limit: number;

  futureJobs: Job[];
  doneJobs: Job[];

    constructor(private timelineService: TimelineService) { }

    ngOnInit(): void {
      this.refreshGrid();

      Observable.interval(1000).subscribe(() => this.filterOutdatedFutureJobs());
      // Observable.interval(5000).subscribe(() => this.refreshGrid());
    }

    private refreshGrid():void
    {
        this.timelineService.getTimelineDone(new GetTimeline(this.jobTemplateId, this.limit))
            .subscribe(jobs => {
                this.doneJobs = jobs;
            });
        this.timelineService.getTimelineFuture(new GetTimeline(this.jobTemplateId, this.limit))
            .subscribe(jobs => {
                this.futureJobs = jobs;
            });
    }

    private filterOutdatedFutureJobs():void
    {
        if (!this.futureJobs) {
            return;
        }

        const previousLength: number = this.futureJobs.length;

        this.futureJobs = this.futureJobs.filter((job: Job) => {
            return job.createdAt.unix > (Date.now() /1000);
        });

        if (previousLength != this.futureJobs.length) {
            this.refreshGrid();
        }
    }
}
