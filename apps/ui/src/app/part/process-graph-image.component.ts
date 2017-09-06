import {Component, Input} from '@angular/core';
import {HttpService} from "../shared/http.service";

@Component({
  selector: 'process-graph-image',
  template: `<img [src]="getApiBaseUrl()+'/process/'+processId+'/graph.png?updatedAt='+updatedAt" />`,
})
export class ProcessGraphImageComponent {
    @Input() processId: string;
    @Input() updatedAt: number;

    constructor(private httpService: HttpService) {
    }

    getApiBaseUrl(): string {
        return this.httpService.getApiBaseUrl();
    }
}
