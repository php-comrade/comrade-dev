import {Component, Input, OnInit} from '@angular/core';
import {HttpService} from "../shared/http.service";
import {Response} from "@angular/http";
import {DomSanitizer, SafeHtml} from "@angular/platform-browser";

@Component({
  selector: 'process-graph-image',
  template: `<div *ngIf="digraph" [innerHTML]="digraph"></div>`,
})
export class ProcessGraphImageComponent implements OnInit {
    @Input() processId: string;
    @Input() updatedAt: number;

    digraph: SafeHtml;

    constructor(private httpService: HttpService, private sanitizer: DomSanitizer) {}

    ngOnInit(): void {
      this.httpService.get('/process/' + this.processId + '/graph.gv?updatedAt=' + this.updatedAt)
        .subscribe((res: Response) => {
          let Viz = require('viz.js');

          this.digraph = this.sanitizer.bypassSecurityTrustHtml(Viz(res.text()));
        });
    }
}
