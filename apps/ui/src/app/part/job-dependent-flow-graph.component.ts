import {Component, Input, OnInit} from '@angular/core';
import {HttpService} from "../shared/http.service";
import {Response} from "@angular/http";
import {DomSanitizer, SafeHtml} from "@angular/platform-browser";

@Component({
  selector: 'job-dependent-flow-graph',
  template: `<div *ngIf="digraph" [innerHTML]="digraph"></div>`,
})
export class JobDependentFlowGraphComponent implements OnInit {
    @Input() jobId: string;
    @Input() jobTemplateId: string;
    @Input() updatedAt: number;

    digraph: SafeHtml;

    constructor(private httpService: HttpService, private sanitizer: DomSanitizer) {}

    ngOnInit(): void {
      if (this.jobId) {


        this.requestGraph('/api/job/' + this.jobId + '/dependent-flow-graph.gv?updatedAt=' + this.updatedAt);
      }

      if (this.jobTemplateId) {
        this.requestGraph('/api/job-template/' + this.jobTemplateId + '/dependent-flow-graph.gv?updatedAt=' + this.updatedAt);
      }
    }

    requestGraph(url: string)
    {
      this.httpService.get(url)
        .subscribe((res: Response) => {
          let Viz = require('viz.js');

          this.digraph = this.sanitizer.bypassSecurityTrustHtml(Viz(res.text()));
        });
    }
}
